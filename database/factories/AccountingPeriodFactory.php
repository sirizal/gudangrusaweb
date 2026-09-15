<?php

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingPeriod>
 */
class AccountingPeriodFactory extends Factory
{
    public function definition(): array
    {
        $periodNumber = fake()->numberBetween(1, 12);
        $year = fake()->numberBetween(2025, 2027);

        return [
            'fiscal_year_id' => FiscalYear::factory(),
            'period_number' => $periodNumber,
            'period_name' => date('F', mktime(0, 0, 0, $periodNumber, 1)),
            'start_date' => "{$year}-".str_pad((string) $periodNumber, 2, '0', STR_PAD_LEFT).'-01',
            'end_date' => "{$year}-".str_pad((string) $periodNumber, 2, '0', STR_PAD_LEFT).'-'.now()->endOfMonth()->day,
            'status' => PeriodStatus::Open,
        ];
    }
}
