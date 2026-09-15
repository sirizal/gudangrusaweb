<?php

namespace Database\Factories;

use App\Enums\FiscalYearStatus;
use App\Models\Company;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalYear>
 */
class FiscalYearFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->numberBetween(2025, 2027);

        return [
            'company_id' => Company::factory(),
            'name' => "FY {$year}",
            'year' => $year,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'status' => FiscalYearStatus::Open,
            'is_current' => false,
        ];
    }
}
