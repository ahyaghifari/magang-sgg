<?php

namespace App\Services\Shift;

use App\Models\Intern;
use App\Models\InternShiftAssignment;
use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use App\Models\User;
use App\Notifications\ShiftChangeDecided;
use App\Notifications\ShiftChangeRequested;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Pengajuan perubahan jadwal shift oleh intern (halaman Jadwal Shift peserta). Intern TIDAK
 * pernah mengubah jadwal langsung — setiap pilihan menjadi pengajuan yang diputuskan Mentor-nya.
 *
 * KESALAHAN (seluruh batch batal): entri tidak valid (lihat ShiftAssignmentService::normalizeEntries),
 * aktor bukan intern itu, intern tidak memakai shift (Intern::usesShifts), belum punya mentor,
 * data unit belum lengkap, alasan kosong / terlalu panjang.
 *
 * DILEWATI (dihitung di ringkasan, batch jalan terus): tanggal di luar hari ini s.d. 30 hari,
 * tanggal yang sudah ada tap (InternShiftAssignmentPolicy::fillOwnDate), tanggal yang sudah punya
 * pengajuan menunggu, dan pilihan yang sama dengan jadwal saat ini.
 *
 * Satu batch = satu transaksi = SATU notifikasi Web Push ke Mentor. Keputusan → intern diberi tahu.
 * Pembimbing dan Pimpinan tidak diberi tahu.
 */
class ShiftChangeRequestService
{
    public function __construct(private readonly ShiftAssignmentService $assignments)
    {
    }

    /**
     * @param  array<int, array{date: string, shift: string}>  $entries  shift: pagi|siang|malam|libur
     * @return array{requested: array<int, string>, skipped: array<int, string>, locked: array<int, string>, pending: array<int, string>, unchanged: array<int, string>}
     *         skipped = gabungan locked + pending + unchanged.
     *
     * @throws ValidationException
     */
    public function submitEntries(User $actor, Intern $intern, array $entries, ?string $reason): array
    {
        $map = ShiftAssignmentService::normalizeEntries($entries);

        if ((int) $intern->user_id !== (int) $actor->id) {
            throw ValidationException::withMessages(['entries' => 'Kamu hanya bisa mengajukan perubahan jadwalmu sendiri.']);
        }
        if (! $intern->usesShifts()) {
            throw ValidationException::withMessages(['entries' => 'Kamu belum terdaftar memakai jadwal shift. Silakan hubungi admin.']);
        }
        if (! $intern->mentor_id) {
            throw ValidationException::withMessages(['entries' => 'Kamu belum punya mentor yang bisa menyetujui perubahan. Silakan hubungi pembimbing.']);
        }

        $companyId = $this->assignments->companyId($intern);
        if (! $companyId) {
            throw ValidationException::withMessages(['entries' => 'Data unit kamu belum lengkap, silakan hubungi admin.']);
        }

        $reason = trim((string) $reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Tulis alasan pengajuan perubahan shift.']);
        }
        if (mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['reason' => 'Alasan maksimal 500 karakter.']);
        }

        $dates = array_keys($map);
        $existing = InternShiftAssignment::with('shift')
            ->where('intern_id', $intern->id)
            ->whereIn('date', $dates)
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $pendingDates = ShiftChangeRequest::where('intern_id', $intern->id)
            ->where('status', ShiftChangeRequest::STATUS_PENDING)
            ->whereIn('date', $dates)
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        // Master shift perusahaan per jenis (id dicatat bila sudah ada; yang belum ada dibuat saat disetujui).
        $masterIds = Shift::where('company_id', $companyId)->pluck('id', 'code');

        $result = ['requested' => [], 'skipped' => [], 'locked' => [], 'pending' => [], 'unchanged' => []];
        $plan = [];

        foreach ($map as $date => $value) {
            $row = $existing->get($date);
            $wantOff = $value === ShiftAssignmentService::ACTION_OFF;

            if (! Gate::forUser($actor)->allows('fillOwnDate', [InternShiftAssignment::class, $intern, $date])) {
                $result['locked'][] = $date;
            } elseif (in_array($date, $pendingDates, true)) {
                $result['pending'][] = $date;
            } elseif ($row && ($wantOff ? $row->off_day : (! $row->off_day && $row->shift?->code === $value))) {
                $result['unchanged'][] = $date;
            } else {
                $plan[$date] = [$row, $wantOff, $value];
            }
        }

        $created = collect();

        DB::transaction(function () use ($intern, $plan, $masterIds, $reason, &$created, &$result) {
            foreach ($plan as $date => [$row, $wantOff, $value]) {
                $created->push(ShiftChangeRequest::create([
                    'intern_id' => $intern->id,
                    'date' => $date,
                    'old_shift_id' => $row?->off_day ? null : $row?->shift_id,
                    'old_off_day' => (bool) $row?->off_day,
                    // Libur dicatat seperti jadwal harian: off_day = true, tanpa shift.
                    'requested_shift_id' => $wantOff ? null : $masterIds->get($value),
                    'requested_shift_code' => $wantOff ? null : $value,
                    'requested_off_day' => $wantOff,
                    'reason' => $reason,
                    'status' => ShiftChangeRequest::STATUS_PENDING,
                ]));
                $result['requested'][] = $date;
            }
        });

        $result['skipped'] = array_merge($result['locked'], $result['pending'], $result['unchanged']);
        sort($result['skipped']);

        if ($created->isNotEmpty()) {
            $intern->loadMissing('mentor');
            $intern->mentor?->notify(new ShiftChangeRequested($intern, $created->each->load(['oldShift', 'requestedShift'])));
        }

        return $result;
    }

    /**
     * Mentor menyetujui / menolak. Disetujui → jadwal diubah lewat ShiftAssignmentService
     * (hitung ulang presensi bila tanggalnya sudah lewat). Intern diberi tahu.
     *
     * @throws ValidationException
     */
    public function decide(User $actor, ShiftChangeRequest $request, bool $approve, ?string $note = null): ShiftChangeRequest
    {
        $intern = $request->intern;

        if (! $intern || ! Gate::forUser($actor)->allows('decideChange', [InternShiftAssignment::class, $intern])) {
            throw ValidationException::withMessages(['request' => 'Hanya mentor dari intern ini yang bisa memutuskan pengajuan.']);
        }

        $note = trim((string) $note);
        if (mb_strlen($note) > 500) {
            throw ValidationException::withMessages(['note' => 'Catatan maksimal 500 karakter.']);
        }

        DB::transaction(function () use ($actor, $request, $intern, $approve, $note) {
            $fresh = ShiftChangeRequest::whereKey($request->id)->lockForUpdate()->first();
            if (! $fresh || ! $fresh->isPending()) {
                throw ValidationException::withMessages(['request' => 'Pengajuan ini sudah tidak menunggu keputusan.']);
            }

            if ($approve) {
                // Master shift yang sudah ada dipakai apa adanya; bila belum ada (atau sudah dihapus admin),
                // jenisnya diteruskan ke apply() yang membuat master shift otomatis dengan jam bawaan.
                $shiftRef = $fresh->requested_shift_id ?? $fresh->requested_shift_code;
                if (! $fresh->requested_off_day && ! $shiftRef) {
                    throw ValidationException::withMessages(['request' => 'Shift yang diajukan sudah tidak ada. Tolak pengajuan ini.']);
                }

                $applied = $this->assignments->apply(
                    $actor,
                    $intern,
                    [$fresh->date->toDateString()],
                    $fresh->requested_off_day ? ShiftAssignmentService::ACTION_OFF : ShiftAssignmentService::ACTION_SHIFT,
                    $fresh->requested_off_day ? null : $shiftRef,
                );

                if ($applied['locked'] !== []) {
                    throw ValidationException::withMessages(['request' => 'Kamu tidak punya akses untuk mengubah jadwal intern ini.']);
                }
            }

            $request->forceFill([
                'status' => $approve ? ShiftChangeRequest::STATUS_APPROVED : ShiftChangeRequest::STATUS_REJECTED,
                'decided_by' => $actor->id,
                'decision_note' => $note !== '' ? $note : null,
                'decided_at' => now(),
            ])->save();
        });

        $request->load(['decider', 'oldShift', 'requestedShift', 'intern.user']);
        $intern->user?->notify(new ShiftChangeDecided($request));

        return $request;
    }

    /** Intern membatalkan pengajuannya sendiri yang masih menunggu. @throws ValidationException */
    public function cancel(User $actor, ShiftChangeRequest $request): void
    {
        if ((int) $request->intern?->user_id !== (int) $actor->id) {
            throw ValidationException::withMessages(['request' => 'Kamu hanya bisa membatalkan pengajuanmu sendiri.']);
        }

        $updated = ShiftChangeRequest::whereKey($request->id)
            ->where('status', ShiftChangeRequest::STATUS_PENDING)
            ->update(['status' => ShiftChangeRequest::STATUS_CANCELLED, 'updated_at' => now()]);

        if (! $updated) {
            throw ValidationException::withMessages(['request' => 'Pengajuan ini sudah tidak menunggu keputusan.']);
        }

        $request->refresh();
    }
}
