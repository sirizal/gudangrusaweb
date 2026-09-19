<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Warehouse> */
class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'warehouse_code' => 'WH-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => 'Warehouse '.fake()->city(),
            'company_id' => Company::factory(),
            'address' => fake()->streetAddress(),
            'postal_code' => (string) fake()->numberBetween(10000, 99999),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
