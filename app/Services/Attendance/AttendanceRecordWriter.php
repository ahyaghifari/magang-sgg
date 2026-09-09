<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;

/**
 * Upsert hasil FixedScheduleCalculator ke attendance_records dengan aturan merge:
 * check_in diambil paling awal, check_out paling akhir — supaya batch incremental
 * yang cuma membawa scan pulang tidak menimpa scan masuk yang sudah benar.
 */
class AttendanceRecordWriter
{
    /** @param  array  $r  hasil FixedScheduleCalculator::calculate() */
    public function save(array $r): AttendanceRecord
    {
        $existing = AttendanceRecord::where('nip', $r['nip'])->where('date', $r['date'])->first();

        $checkIn = $this->earliest($existing?->check_in_time, $r['check_in_time']);
        $checkOut = $this->latest($existing?->check_out_time, $r['check_out_time']);

        // Penyederhanaan (lihat dokumen §6.2): pada jalur incremental normal, check_in
        // sudah tetap setelah scan pertama; batch berikutnya hanya menambah check_out,
        // jadi late_minutes dari $r tetap benar.
        return AttendanceRecord::updateOrCreate(
            ['nip' => $r['nip'], 'date' => $r['date']],
            [
                'company_id' => $r['company_id'],
                'check_in_time' => $checkIn,
                'check_out_time' => $checkOut,
                'late_minutes' => $r['late_minutes'],
                'early_leave_minutes' => $r['early_leave_minutes'],
                'working_minutes' => $r['working_minutes'],
                'status' => $r['status'],
                'out_of_window' => $r['out_of_window'],
                'source' => 'access_logs_fixed',
            ],
        );
    }

    private function earliest(?string $a, ?string $b): ?string
    {
        return collect([$a, $b])->filter()->min();
    }

    private function latest(?string $a, ?string $b): ?string
    {
        return collect([$a, $b])->filter()->max();
    }
}
