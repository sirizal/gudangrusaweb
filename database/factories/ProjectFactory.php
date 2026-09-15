<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'PRJ-'.fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->unique()->catchPhrase(),
            'is_active' => true,
        ];
    }
}
