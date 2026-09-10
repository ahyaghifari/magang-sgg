<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use Illuminate\Support\Collection;

/**
 * Upsert hasil FixedScheduleCalculator ke attendance_records.
 *
 * Karena sync incremental sering hanya membawa SATU tap per batch, satu tap tidak
 * bisa langsung dipastikan check-in atau check-out. Maka bila sudah ada baris untuk
 * (nip, tanggal), semua waktu tap yang pernah terlihat digabung: yang paling awal
 * jadi check_in, yang paling akhir jadi check_out, lalu menit & status DIHITUNG ULANG
 * dari pasangan itu (bukan dari $r yang cuma melihat satu tap).
 */
class AttendanceRecordWriter
{
    public function __construct(private FixedScheduleCalculator $calculator) {}

    /** @param  array  $r  hasil FixedScheduleCalculator::calculate() */
    public function save(array $r): AttendanceRecord
    {
        $existing = AttendanceRecord::where('nip', $r['nip'])->where('date', $r['date'])->first();

        if (! $existing) {
            // Baris baru: percayai $r apa adanya (single-scan-after-end sudah ditangani calculator).
            return AttendanceRecord::create([
                'nip' => $r['nip'],
                'company_id' => $r['company_id'],
                'date' => $r['date'],
                'check_in_time' => $r['check_in_time'],
                'check_out_time' => $r['check_out_time'],
                'late_minutes' => $r['late_minutes'],
                'early_leave_minutes' => $r['early_leave_minutes'],
                'working_minutes' => $r['working_minutes'],
                'status' => $r['status'],
                'out_of_window' => $r['out_of_window'],
                'source' => 'access_logs_fixed',
            ]);
        }

        // Gabung semua waktu tap yang pernah terlihat -> paling awal & paling akhir.
        $times = $this->sortedTimes([
            $existing->check_in_time,
            $existing->check_out_time,
            $r['check_in_time'],
            $r['check_out_time'],
        ]);

        $checkIn = $times->first();
        $checkOut = $times->count() > 1 ? $times->last() : null;

        $recalc = $this->calculator->calculate([
            'nip' => $r['nip'],
            'company_id' => $r['company_id'],
            'date' => $r['date'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'single_scan' => false,
        ]) ?? $r;

        $existing->update([
            'company_id' => $r['company_id'],
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'late_minutes' => $recalc['late_minutes'],
            'early_leave_minutes' => $recalc['early_leave_minutes'],
            'working_minutes' => $recalc['working_minutes'],
            'status' => $recalc['status'],
            'out_of_window' => $recalc['out_of_window'],
            'source' => 'access_logs_fixed',
        ]);

        return $existing;
    }

    /** @param  array<int, ?string>  $values  jam "HH:MM:SS" */
    private function sortedTimes(array $values): Collection
    {
        return collect($values)
            ->map(fn ($v) => $v ? substr((string) $v, 0, 8) : null)
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
