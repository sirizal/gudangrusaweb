<?php

namespace Database\Factories;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryStock> */
class InventoryStockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'location_id' => WarehouseLocation::factory(),
            'quantity' => fake()->numberBetween(0, 100),
            'reserved_quantity' => 0,
        ];
    }
}
