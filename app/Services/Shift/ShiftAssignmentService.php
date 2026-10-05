<?php

namespace App\Services\Shift;

use App\Models\Intern;
use App\Models\InternShiftAssignment;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\AttendanceRecalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya jalur untuk mengubah jadwal shift intern (dipakai halaman Jadwal Shift Intern
 * milik Pembimbing/Mentor), supaya aturan selalu sama:
 * - hak akses: InternShiftAssignmentPolicy::manageDate() — hanya Pembimbing (binaannya) dan
 *   Mentor (dampingannya); tanggal yang tidak boleh diubah DILEWATI, dilaporkan sebagai 'locked';
 * - shift harus milik perusahaan intern (intern → unit → company);
 * - Libur = off_day true tanpa shift; "kosongkan" = hapus entri (kembali "belum diisi");
 * - created_by diisi saat entri dibuat, updated_by setiap kali diubah.
 *
 * Tidak menyentuh izin sama sekali (izin tidak mengubah jadwal & sebaliknya).
 */
class ShiftAssignmentService
{
    public const ACTION_SHIFT = 'shift';

    public const ACTION_OFF = 'off';

    public const ACTION_CLEAR = 'clear';

    /** Batas jumlah tanggal per sekali terapkan (cukup untuk 2 bulan). */
    public const MAX_DATES = 62;

    /**
     * Terapkan shift / Libur / kosongkan ke beberapa tanggal sekaligus.
     *
     * @param  array<int, string>  $dates  tanggal Y-m-d
     * @param  int|string|null  $shiftId  id master shift, atau jenis baku ('Pagi'/'Siang') — jenis yang
     *         belum punya master shift di perusahaan peserta dibuat otomatis dengan jam bawaan
     *         (hanya oleh Pembimbing/Mentor yang berhak).
     * @return array{saved: array<int, string>, cleared: array<int, string>, locked: array<int, string>, past_changed: array<int, string>}
     *         past_changed = tanggal hari ini/lampau yang benar-benar berubah (perlu hitung ulang presensi).
     *
     * @throws ValidationException
     */
    public function apply(User $actor, Intern $intern, array $dates, string $action, int|string|null $shiftId = null): array
    {
        if (! in_array($action, [self::ACTION_SHIFT, self::ACTION_OFF, self::ACTION_CLEAR], true)) {
            throw ValidationException::withMessages(['action' => 'Pilihan tidak dikenal.']);
        }

        $dates = collect($dates)
            ->filter(fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false)
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            throw ValidationException::withMessages(['dates' => 'Silakan pilih minimal satu tanggal.']);
        }
        if ($dates->count() > self::MAX_DATES) {
            throw ValidationException::withMessages(['dates' => 'Maksimal ' . self::MAX_DATES . ' tanggal sekali terapkan.']);
        }

        $shift = null;
        if ($action === self::ACTION_SHIFT) {
            if (is_string($shiftId) && ! ctype_digit($shiftId)) {
                $shiftId = $this->shiftIdForType($actor, $intern, $shiftId);
            }
            $shift = $this->shiftForIntern($actor, $intern, $shiftId === null ? null : (int) $shiftId);
        }

        $result = ['saved' => [], 'cleared' => [], 'locked' => [], 'past_changed' => []];
        $today = Carbon::today()->toDateString();

        DB::transaction(function () use ($actor, $intern, $dates, $action, $shift, $today, &$result) {
            $existing = InternShiftAssignment::where('intern_id', $intern->id)
                ->whereIn('date', $dates->all())
                ->get()
                ->keyBy(fn ($a) => $a->date->toDateString());

            foreach ($dates as $date) {
                if (! Gate::forUser($actor)->allows('manageDate', [InternShiftAssignment::class, $intern, $date])) {
                    $result['locked'][] = $date;
                    continue;
                }

                $row = $existing->get($date);

                if ($action === self::ACTION_CLEAR) {
                    if ($row) {
                        $row->delete();
                        $result['cleared'][] = $date;
                        if ($date <= $today) {
                            $result['past_changed'][] = $date;
                        }
                    }
                    continue;
                }

                $values = $action === self::ACTION_OFF
                    ? ['off_day' => true, 'shift_id' => null]
                    : ['off_day' => false, 'shift_id' => $shift->id];

                $changed = ! $row || (bool) $row->off_day !== $values['off_day'] || (int) $row->shift_id !== (int) $values['shift_id'];

                if (! $row) {
                    InternShiftAssignment::create($values + [
                        'intern_id' => $intern->id,
                        'date' => $date,
                        'created_by' => $actor->id,
                        'updated_by' => $actor->id,
                    ]);
                } elseif ($changed) {
                    $row->update($values + ['updated_by' => $actor->id]);
                }

                $result['saved'][] = $date;
                if ($changed && $date <= $today) {
                    $result['past_changed'][] = $date;
                }
            }
        });

        // Koreksi jadwal hari ini/lampau → hitung ulang presensi tanggal itu supaya nama shift
        // dan telat/pulang cepat konsisten (tanggal ke depan belum punya presensi).
        if ($result['past_changed'] !== []) {
            app(AttendanceRecalculator::class)->recalc($intern, $result['past_changed']);
        }

        return $result;
    }

    /**
     * Shift yang boleh dipilih untuk intern ini, urut jam masuk:
     * - intern sendiri → hanya shift perusahaannya tempat dia TERDAFTAR (dipilih admin di Master Shift);
     * - Admin/Pembimbing/Mentor yang mengoreksi → semua shift perusahaan intern (bisa mengoreksi
     *   walau daftar intern di master shift belum diperbarui).
     */
    public function availableShifts(Intern $intern, ?User $actor = null)
    {
        $companyId = $this->companyId($intern);

        if (! $companyId) {
            return collect();
        }

        $query = Shift::where('company_id', $companyId)->orderBy('start_time');

        if (! $actor || $actor->id === $intern->user_id) {
            $query->whereHas('interns', fn ($q) => $q->whereKey($intern->id));
        }

        return $query->get();
    }

    /**
     * Pilihan shift di panel Pembimbing/Mentor: SEMUA jenis baku (Shift::TYPES — Pagi & Siang) selalu
     * tersedia. Master shift perusahaan yang sudah ada dipakai apa adanya (jam dari admin); jenis yang
     * belum ada tampil sebagai model BELUM TERSIMPAN (id null, jam bawaan) dan baru dibuat saat
     * diterapkan (lihat shiftIdForType). Shift lain buatan admin tetap ikut. Urut jam masuk.
     */
    public function pickableShifts(Intern $intern)
    {
        $companyId = $this->companyId($intern);

        if (! $companyId) {
            return collect();
        }

        $existing = Shift::where('company_id', $companyId)->get();
        $missing = collect(Shift::TYPES)
            ->reject(fn ($type) => $existing->contains('code', $type))
            ->map(fn ($type) => new Shift(['company_id' => $companyId, 'code' => $type] + self::defaultAttributes($type)));

        return $existing->concat($missing)->sortBy('start_time')->values();
    }

    /** Jam & toleransi bawaan untuk jenis shift baku (sama dengan bawaan form Master Shift admin). */
    public static function defaultAttributes(string $type): array
    {
        return [
            'start_time' => Shift::DEFAULT_TIMES[$type]['start'] . ':00',
            'end_time' => Shift::DEFAULT_TIMES[$type]['end'] . ':00',
            'break_minutes' => 0,
            'late_tolerance_minutes' => 0,
            'early_leave_tolerance_minutes' => 0,
            'checkin_buffer_minutes' => 120,
            'checkout_buffer_minutes' => 240,
        ];
    }

    /**
     * Id master shift jenis baku untuk perusahaan peserta — dibuat dengan jam bawaan bila belum ada.
     * Hanya untuk yang berhak mengoreksi (Pembimbing binaannya / Mentor dampingannya).
     *
     * @throws ValidationException
     */
    private function shiftIdForType(User $actor, Intern $intern, string $type): int
    {
        if (! in_array($type, Shift::TYPES, true)) {
            throw ValidationException::withMessages(['shift' => 'Jenis shift tidak dikenal.']);
        }
        if (! Gate::forUser($actor)->allows('correctAnyDate', [InternShiftAssignment::class, $intern])) {
            throw ValidationException::withMessages(['shift' => 'Kamu tidak punya akses untuk mengubah jadwal peserta ini.']);
        }

        $companyId = $this->companyId($intern);
        if (! $companyId) {
            throw ValidationException::withMessages(['shift' => 'Data unit peserta ini belum lengkap, silakan hubungi admin.']);
        }

        return Shift::firstOrCreate(['company_id' => $companyId, 'code' => $type], self::defaultAttributes($type))->id;
    }

    /** Perusahaan intern (lewat unit), null kalau data unit belum lengkap. */
    public function companyId(Intern $intern): ?int
    {
        $intern->loadMissing('unit');

        return $intern->unit?->company_id;
    }

    /** @throws ValidationException */
    private function shiftForIntern(User $actor, Intern $intern, ?int $shiftId): Shift
    {
        if (! $this->companyId($intern)) {
            throw ValidationException::withMessages(['shift' => $actor->id === $intern->user_id
                ? 'Data unit kamu belum lengkap, silakan hubungi admin.'
                : 'Data unit peserta ini belum lengkap, silakan hubungi admin.']);
        }

        $shift = $shiftId ? $this->availableShifts($intern, $actor)->firstWhere('id', $shiftId) : null;

        if (! $shift) {
            throw ValidationException::withMessages(['shift' => $actor->id === $intern->user_id
                ? 'Shift ini belum didaftarkan untuk kamu. Silakan hubungi admin.'
                : 'Shift tidak ditemukan untuk perusahaan peserta ini.']);
        }

        return $shift;
    }
}
