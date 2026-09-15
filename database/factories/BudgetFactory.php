<?php

namespace Database\Factories;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Company;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'budget_code' => 'BUD-'.fake()->unique()->numerify('####'),
            'budget_name' => fake()->sentence(3),
            'fiscal_year_id' => FiscalYear::factory(),
            'version' => 'V1',
            'status' => BudgetStatus::Draft,
            'is_active' => false,
            'description' => null,
            'submitted_by' => null,
            'approved_by' => null,
            'submitted_at' => null,
            'approved_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => BudgetStatus::Draft,
            'is_active' => false,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'status' => BudgetStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => BudgetStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => BudgetStatus::Rejected,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (): array => [
            'status' => BudgetStatus::Locked,
            'approved_at' => now(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'is_active' => true,
        ]);
    }

    public function ofFiscalYear(FiscalYear $fiscalYear): static
    {
        return $this->state(fn (): array => [
            'company_id' => $fiscalYear->company_id,
            'fiscal_year_id' => $fiscalYear->id,
        ]);
    }
}
