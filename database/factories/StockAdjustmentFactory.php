<?php

namespace Database\Factories;

use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockAdjustment> */
class StockAdjustmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'adjustment_code' => 'ADJ-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'warehouse_id' => Warehouse::factory(),
            'location_id' => WarehouseLocation::factory(),
            'adjustment_date' => now()->toDateString(),
            'status' => 'posted',
            'total_cost' => 0,
            'reason' => fake()->sentence(),
        ];
    }
}
