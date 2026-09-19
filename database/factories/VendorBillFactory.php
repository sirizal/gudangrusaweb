<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Vendor;
use App\Models\VendorBill;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VendorBill> */
class VendorBillFactory extends Factory
{
    public function definition(): array
    {
        $billDate = fake()->date();

        return [
            'bill_code' => 'VB-'.substr($billDate, 0, 4).'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'company_id' => Company::factory(),
            'vendor_id' => Vendor::factory(),
            'bill_date' => $billDate,
            'due_date' => Carbon::parse($billDate)->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'paid_amount' => 0,
        ];
    }
}
