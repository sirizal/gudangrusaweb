<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VendorPayment> */
class VendorPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_code' => 'PV-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'company_id' => Company::factory(),
            'vendor_id' => Vendor::factory(),
            'payment_date' => now()->toDateString(),
            'total' => 0,
            'reference' => fake()->numerify('PAY-####'),
            'status' => 'posted',
        ];
    }
}
