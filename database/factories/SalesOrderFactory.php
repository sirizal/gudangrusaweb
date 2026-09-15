<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PaymentTerm;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_code' => 'SO-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'billing_address_id' => CustomerAddress::factory(),
            'shipping_address_id' => CustomerAddress::factory(),
            'order_date' => now()->toDateString(),
            'payment_term_id' => PaymentTerm::factory(),
            'status' => 'raised',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ];
    }
}
