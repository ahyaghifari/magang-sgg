<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Dipakai lewat UnitFactory::new() (model tidak perlu trait HasFactory). */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Unit ' . fake()->unique()->word(),
        ];
    }
}
