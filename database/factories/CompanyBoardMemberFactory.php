<?php

namespace Database\Factories;

use App\Enums\CompanyBoardPosition;
use App\Models\Company;
use App\Models\CompanyBoardMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyBoardMember>
 */
class CompanyBoardMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'position' => fake()->randomElement(CompanyBoardPosition::cases())->value,
            'nik' => fake()->numerify('################'),
            'start_date' => fake()->date(),
            'end_date' => fake()->optional()->date(),
            'is_active' => true,
        ];
    }
}
