<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PaymentTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_code' => 'CUST-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => 'https://'.fake()->domainName(),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'is_pkp' => fake()->boolean(),
            'credit_limit' => fake()->randomElement([0, 5000000, 10000000, 25000000]),
            'payment_term_id' => PaymentTerm::factory(),
            'is_active' => true,
        ];
    }
}
