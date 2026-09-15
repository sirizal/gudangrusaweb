<?php

namespace Database\Factories;

use App\Enums\StatementType;
use App\Models\FinancialStatementLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialStatementLine>
 */
class FinancialStatementLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'statement_type' => fake()->randomElement(StatementType::cases()),
            'code' => fake()->unique()->lexify('LINE-????'),
            'name' => fake()->words(2, true),
            'sequence' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
