<?php

namespace Database\Factories;

use App\Models\VendorBill;
use App\Models\VendorPayment;
use App\Models\VendorPaymentLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VendorPaymentLine> */
class VendorPaymentLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_payment_id' => VendorPayment::factory(),
            'vendor_bill_id' => VendorBill::factory(),
            'amount' => fake()->numberBetween(10000, 500000),
        ];
    }
}
