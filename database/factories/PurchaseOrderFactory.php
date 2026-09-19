<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseOrder> */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'po_code' => 'PO-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'company_id' => Company::factory(),
            'vendor_id' => Vendor::factory(),
            'po_date' => now()->toDateString(),
            'expected_date' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ];
    }
}
