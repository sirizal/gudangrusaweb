<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrderLine>
 */
class SalesOrderLineFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(10000, 1000000);

        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => Product::factory(),
            'description' => fake()->words(4, true),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice,
        ];
    }
}
