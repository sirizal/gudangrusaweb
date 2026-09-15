<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'DEPT-'.fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->unique()->company(),
            'parent_id' => null,
            'is_active' => true,
        ];
    }
}
