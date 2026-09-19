<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockTransferLine> */
class StockTransferLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stock_transfer_id' => StockTransfer::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
