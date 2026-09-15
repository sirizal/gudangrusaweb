<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CostCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostCenter>
 */
class CostCenterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'CC-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => fake()->unique()->company(),
            'parent_id' => null,
            'manager' => fake()->name(),
            'is_active' => true,
        ];
    }
}
