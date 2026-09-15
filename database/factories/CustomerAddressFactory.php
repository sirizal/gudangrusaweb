<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        $village = Village::query()->first() ?? Village::factory();

        return [
            'customer_id' => Customer::factory(),
            'address_code' => fake()->unique()->lexify('ADDR-????'),
            'label' => fake()->randomElement(['Head Office', 'Warehouse', 'Billing', 'Branch']),
            'address' => fake()->streetAddress(),
            'village_id' => $village instanceof Village ? $village->id : null,
            'sub_district_id' => $village instanceof Village ? $village->sub_district_id : null,
            'district_id' => $village instanceof Village ? $village->subDistrict->district_id : null,
            'province_id' => $village instanceof Village ? $village->subDistrict->district->province_id : null,
            'country_id' => $village instanceof Village ? $village->subDistrict->district->province->country_id : null,
            'postal_code' => (string) fake()->numberBetween(10000, 99999),
            'phone' => fake()->phoneNumber(),
            'is_billing' => true,
            'is_shipping' => true,
            'is_default' => true,
        ];
    }
}
