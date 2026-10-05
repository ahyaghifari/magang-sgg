<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Dipakai lewat InstitutionFactory::new() (model tidak perlu trait HasFactory). */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        return [
            'name' => 'Universitas ' . fake()->unique()->city(),
            'address' => fake()->address(),
        ];
    }
}
