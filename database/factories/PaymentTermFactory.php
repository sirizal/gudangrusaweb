<?php

namespace Database\Factories;

use App\Models\PaymentTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTerm>
 */
class PaymentTermFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'NET-'.fake()->unique()->numerify('#####'),
            'name' => 'Net '.fake()->numberBetween(7, 60),
            'due_days' => fake()->randomElement([7, 14, 30, 45, 60]),
            'is_active' => true,
        ];
    }
}
