<?php

namespace Database\Factories;

use App\Models\TaxCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxCode>
 */
class TaxCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('TAX-????'),
            'name' => fake()->words(2, true),
            'tax_type' => fake()->randomElement(['PPN', 'PPh 21', 'PPh 22', 'PPh 23', 'PPh 4(2)', 'PPh 25', 'PPh 29']),
            'rate' => fake()->randomElement(['0', '0.1100', '0.0200', '0.0150', '0.0500']),
            'account_id' => null,
            'payable_account_id' => null,
            'receivable_account_id' => null,
            'is_active' => true,
        ];
    }
}
