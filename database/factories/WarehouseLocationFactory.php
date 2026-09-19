<?php

namespace Database\Factories;

use App\Enums\WarehouseLocationType;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WarehouseLocation> */
class WarehouseLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'location_code' => fake()->unique()->lexify('LOC-????'),
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(WarehouseLocationType::cases())->value,
            'weight_capacity' => fake()->numberBetween(100, 10000),
            'volume_capacity' => fake()->numberBetween(10, 1000),
            'is_active' => true,
        ];
    }
}
