<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 *
 * Jenis shift (code) unik per perusahaan — tiap factory bawaan membuat perusahaan baru.
 * Untuk dua shift di satu perusahaan, pakai state berbeda + company_id yang sama.
 */
class ShiftFactory extends Factory
{
    /** Bawaan: shift Pagi 08:30–16:30. */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'Pagi',
            'start_time' => '08:30:00',
            'end_time' => '16:30:00',
            'break_minutes' => 60,
            'late_tolerance_minutes' => 10,
            'early_leave_tolerance_minutes' => 0,
            'checkin_buffer_minutes' => 120,
            'checkout_buffer_minutes' => 240,
        ];
    }

    /** Shift Siang 12:00–21:00. */
    public function siang(): static
    {
        return $this->state(fn () => ['code' => 'Siang', 'start_time' => '12:00:00', 'end_time' => '21:00:00']);
    }
}
