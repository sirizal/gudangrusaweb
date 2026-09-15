<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoicePayment>
 */
class InvoicePaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'payment_date' => fake()->date(),
            'amount' => fake()->numberBetween(10000, 1000000),
            'reference' => fake()->numerify('PAY-####'),
        ];
    }
}
