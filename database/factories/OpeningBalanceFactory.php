<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\OpeningBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningBalance>
 */
class OpeningBalanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fiscal_year_id' => FiscalYear::factory(),
            'account_id' => Account::factory(),
            'debit' => 0,
            'credit' => 0,
        ];
    }

    public function debit(string $amount): static
    {
        return $this->state(fn (): array => [
            'debit' => $amount,
            'credit' => 0,
        ]);
    }

    public function credit(string $amount): static
    {
        return $this->state(fn (): array => [
            'debit' => 0,
            'credit' => $amount,
        ]);
    }
}
