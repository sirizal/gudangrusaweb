<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockAdjustmentLine> */
class StockAdjustmentLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stock_adjustment_id' => StockAdjustment::factory(),
            'product_id' => Product::factory(),
            'quantity_delta' => fake()->numberBetween(-10, 10),
            'unit_cost' => fake()->numberBetween(1000, 100000),
            'cost' => 0,
        ];
    }
}
