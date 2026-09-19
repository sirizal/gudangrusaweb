<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockMovement> */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'location_id' => WarehouseLocation::factory(),
            'type' => fake()->randomElement(StockMovementType::cases())->value,
            'quantity' => fake()->numberBetween(1, 50),
            'unit_cost' => fake()->numberBetween(1000, 100000),
            'total_cost' => 0,
            'movement_date' => now()->toDateString(),
        ];
    }
}
