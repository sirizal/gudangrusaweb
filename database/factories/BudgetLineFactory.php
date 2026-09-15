<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'account_id' => Account::factory(),
            'cost_center_id' => null,
            'department_id' => null,
            'project_id' => null,
            'description' => fake()->sentence(),
            'annual_amount' => 0,
        ];
    }

    public function ofBudget(Budget $budget): static
    {
        return $this->state(fn (): array => [
            'budget_id' => $budget->id,
        ]);
    }
}
