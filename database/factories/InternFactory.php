<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Intern;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Dipakai lewat InternFactory::new() (model tidak perlu trait HasFactory). */
class InternFactory extends Factory
{
    protected $model = Intern::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Intern]),
            'institusi_id' => InstitutionFactory::new(),
            'unit_id' => UnitFactory::new(),
            'nama' => fake()->name(),
            'nip' => (string) fake()->unique()->numberBetween(900000, 999999),
            'jenis_kelamin' => 'L',
        ];
    }

    /** Intern tanpa unit (data unit belum lengkap). */
    public function withoutUnit(): static
    {
        return $this->state(fn () => ['unit_id' => null]);
    }
}
