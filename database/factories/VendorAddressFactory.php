<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorAddress;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VendorAddress> */
class VendorAddressFactory extends Factory
{
    public function definition(): array
    {
        $village = Village::query()->first();

        return [
            'vendor_id' => Vendor::factory(),
            'address_code' => fake()->unique()->lexify('ADDR-????'),
            'label' => fake()->randomElement(['Head Office', 'Warehouse', 'Billing']),
            'address' => fake()->streetAddress(),
            'village_id' => $village?->id,
            'sub_district_id' => $village?->sub_district_id,
            'district_id' => $village?->subDistrict?->district_id,
            'province_id' => $village?->subDistrict?->district?->province_id,
            'country_id' => $village?->subDistrict?->district?->province?->country_id,
            'postal_code' => (string) fake()->numberBetween(10000, 99999),
            'phone' => fake()->phoneNumber(),
            'is_default' => true,
        ];
    }
}
