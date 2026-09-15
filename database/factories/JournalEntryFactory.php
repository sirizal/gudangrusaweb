<?php

namespace Database\Factories;

use App\Enums\JournalStatus;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_number' => 'JV-'.fake()->unique()->numerify('#########'),
            'journal_date' => fake()->date(),
            'accounting_period_id' => AccountingPeriod::factory(),
            'reference_type' => null,
            'reference_id' => null,
            'description' => fake()->sentence(),
            'status' => JournalStatus::Draft,
            'source' => 'manual',
            'reversed_journal_id' => null,
            'is_reversed' => false,
            'posted_at' => null,
            'posted_by' => null,
        ];
    }
}
