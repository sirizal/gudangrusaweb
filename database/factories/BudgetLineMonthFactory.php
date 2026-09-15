<?php

namespace Database\Factories;

use App\Models\BudgetLine;
use App\Models\BudgetLineMonth;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetLineMonth>
 */
class BudgetLineMonthFactory extends Factory
{
    public function definition(): array
    {
        return [
            'budget_line_id' => BudgetLine::factory(),
            'month' => fake()->numberBetween(1, 12),
            'amount' => 0,
        ];
    }

    public function forMonth(int $month): static
    {
        return $this->state(fn (): array => [
            'month' => $month,
        ]);
    }

    public function amount(string $amount): static
    {
        return $this->state(fn (): array => [
            'amount' => $amount,
        ]);
    }
}
