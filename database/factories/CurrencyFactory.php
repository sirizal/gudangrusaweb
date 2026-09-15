<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->currencyCode(),
            'name' => fake()->word(),
            'symbol' => fake()->randomElement(['Rp', '$', '€']),
            'decimal_places' => 2,
            'is_base_currency' => false,
            'is_active' => true,
        ];
    }
}
