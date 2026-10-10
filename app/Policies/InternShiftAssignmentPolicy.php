<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccessScanLog;
use App\Models\AttendanceRecord;
use App\Models\Intern;
use App\Models\InternShiftAssignment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Hak akses jadwal shift intern (dipakai di portal, bukan panel Filament).
 *
 * MELIHAT jadwal seorang intern:
 * - intern itu sendiri;
 * - user yang intern-nya kelihatan di portal (User::visibleInterns(): Admin semua,
 *   Pembimbing binaannya, Mentor semua intern);
 * - Pimpinan (semua intern, baca-saja);
 * - super admin.
 *
 * MENGUBAH (buat/ubah/hapus) entri pada suatu tanggal secara langsung — HANYA Mentor untuk
 * intern dampingannya sendiri (mentor_id), kapan saja, termasuk tanggal lampau. Pembimbing,
 * Admin, Pimpinan, dan super admin hanya melihat.
 *
 * INTERN tidak pernah mengubah jadwal langsung: ia hanya MENGAJUKAN perubahan (fillOwnDate —
 * tanggal hari ini s.d. OWN_WINDOW_DAYS ke depan yang belum ada tap sidik jari/presensinya),
 * yang diputuskan Mentor-nya (decideChange). Lihat App\Services\Shift\ShiftChangeRequestService.
 *
 * Contoh pakai: Gate::allows('viewSchedule', [InternShiftAssignment::class, $intern])
 *               Gate::allows('manageDate', [InternShiftAssignment::class, $intern, $date])
 */
class InternShiftAssignmentPolicy
{
    /** Intern hanya bisa mengatur jadwalnya sampai sekian hari ke depan (dihitung dari hari ini). */
    public const OWN_WINDOW_DAYS = 30;

    public function viewSchedule(User $user, Intern $intern): bool
    {
        return $intern->user_id === $user->id
            || $user->isSuperAdmin()
            || $user->isPimpinan()
            || $user->visibleInterns()->whereKey($intern->id)->exists();
    }

    public function manageDate(User $user, Intern $intern, CarbonInterface|string $date): bool
    {
        return $this->correctAnyDate($user, $intern);
    }

    /** Boleh mengisi/mengoreksi jadwal langsung (tanggal mana saja): HANYA Mentor dampingannya. */
    public function correctAnyDate(User $user, Intern $intern): bool
    {
        if ($user->isSuperAdmin() || $user->isViewingAsIntern()) {
            return false;
        }

        return $user->isMentor() && $intern->mentor_id !== null && (int) $intern->mentor_id === (int) $user->id;
    }

    /**
     * Intern mengajukan perubahan jadwalnya sendiri pada tanggal ini: hanya hari ini
     * s.d. OWN_WINDOW_DAYS hari ke depan, dan belum ada tap sidik jari/presensi di tanggal itu.
     */
    public function fillOwnDate(User $user, Intern $intern, CarbonInterface|string $date): bool
    {
        if ((int) $intern->user_id !== (int) $user->id) {
            return false;
        }

        $date = Carbon::parse($date)->startOfDay();
        $today = Carbon::today();

        if ($date->lt($today) || $date->gt($today->copy()->addDays(self::OWN_WINDOW_DAYS))) {
            return false;
        }

        return ! self::hasTap($intern, $date->toDateString());
    }

    /** Menyetujui/menolak pengajuan perubahan shift: HANYA Mentor dari intern tersebut. */
    public function decideChange(User $user, Intern $intern): bool
    {
        if ($user->isSuperAdmin() || $user->isViewingAsIntern()) {
            return false;
        }

        return $user->isMentor() && $intern->mentor_id !== null && (int) $intern->mentor_id === (int) $user->id;
    }

    /** Sudah ada tap sidik jari / rekap presensi intern pada tanggal ini? */
    public static function hasTap(Intern $intern, string $date): bool
    {
        return self::tappedDates($intern, $date, $date) !== [];
    }

    /**
     * Tanggal (Y-m-d) dalam rentang yang sudah punya tap sidik jari (log mentah) atau rekap
     * presensi berisi jam masuk/pulang — tanggal ini terkunci untuk diatur intern sendiri.
     *
     * @return array<int, string>
     */
    public static function tappedDates(Intern $intern, string $from, string $to): array
    {
        $scans = AccessScanLog::where('intern_id', $intern->id)
            ->whereBetween('scan_date', [$from, $to])
            ->pluck('scan_date');

        $records = $intern->nip !== null && $intern->nip !== ''
            ? AttendanceRecord::where('nip', $intern->nip)
                ->whereBetween('date', [$from, $to])
                ->where(fn ($q) => $q->whereNotNull('check_in_time')->orWhereNotNull('check_out_time'))
                ->pluck('date')
            : collect();

        return $scans->concat($records)
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->all();
    }
}
