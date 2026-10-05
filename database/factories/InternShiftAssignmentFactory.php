<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InternShiftAssignment>
 *
 * intern_id wajib diisi saat memakai factory ini (belum ada InternFactory — dibuat di tahap tes).
 */
class InternShiftAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => now()->addDay()->toDateString(),
            'shift_id' => Shift::factory(),
            'off_day' => false,
        ];
    }

    /** Entri Libur. */
    public function libur(): static
    {
        return $this->state(fn () => ['shift_id' => null, 'off_day' => true]);
    }
}
