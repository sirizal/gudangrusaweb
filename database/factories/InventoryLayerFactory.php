<?php

namespace Database\Factories;

use App\Models\InventoryLayer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryLayer> */
class InventoryLayerFactory extends Factory
{
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 50);

        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'location_id' => WarehouseLocation::factory(),
            'quantity_received' => $qty,
            'quantity_remaining' => $qty,
            'unit_cost' => fake()->numberBetween(1000, 100000),
            'received_date' => now()->toDateString(),
        ];
    }
}
