<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyFixedSchedule;
use Illuminate\Database\Seeder;

/**
 * Jadwal kerja magang untuk SEMUA perusahaan:
 * - Senin–Jumat : 08.30 – 16.30
 * - Sabtu        : 08.30 – 13.30
 * - Minggu       : libur
 *
 * day_of_week mengikuti Carbon::dayOfWeek → 0 Minggu … 6 Sabtu.
 * Dijalankan berulang aman (updateOrCreate).
 */
class CompanyFixedScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            0 => ['off' => true],                                   // Minggu
            1 => ['start' => '08:30:00', 'end' => '16:30:00'],      // Senin
            2 => ['start' => '08:30:00', 'end' => '16:30:00'],      // Selasa
            3 => ['start' => '08:30:00', 'end' => '16:30:00'],      // Rabu
            4 => ['start' => '08:30:00', 'end' => '16:30:00'],      // Kamis
            5 => ['start' => '08:30:00', 'end' => '16:30:00'],      // Jumat
            6 => ['start' => '08:30:00', 'end' => '13:30:00'],      // Sabtu
        ];

        Company::query()->each(function (Company $company) use ($rows) {
            foreach ($rows as $dow => $cfg) {
                CompanyFixedSchedule::updateOrCreate(
                    ['company_id' => $company->id, 'day_of_week' => $dow],
                    [
                        'is_off_day' => $cfg['off'] ?? false,
                        'start_time' => $cfg['start'] ?? null,
                        'end_time' => $cfg['end'] ?? null,
                        'break_minutes' => 0,
                        'late_tolerance_minutes' => 0,
                        'early_leave_tolerance_minutes' => 0,
                        // Buffer longgar: hanya untuk menandai scan yang benar-benar
                        // jauh di luar jam wajar (tidak memengaruhi telat / menit kerja).
                        'checkin_buffer_minutes' => 120,
                        'checkout_buffer_minutes' => 240,
                    ],
                );
            }
        });
    }
}
