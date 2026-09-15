<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntryLine>
 */
class JournalEntryLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'cost_center_id' => null,
            'department_id' => null,
            'project_id' => null,
            'description' => fake()->sentence(),
            'debit' => 0,
            'credit' => 0,
            'tax_code_id' => null,
            'reference' => null,
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
