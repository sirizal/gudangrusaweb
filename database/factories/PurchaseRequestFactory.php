<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PurchaseRequest;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseRequest> */
class PurchaseRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_code' => 'PR-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'company_id' => Company::factory(),
            'vendor_id' => Vendor::factory(),
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'total' => 0,
        ];
    }
}
