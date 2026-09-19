<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseOrderLine> */
class PurchaseOrderLineFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(10000, 500000);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'purchase_type' => 'inventory',
            'product_id' => Product::factory(),
            'description' => fake()->words(4, true),
            'quantity' => fake()->numberBetween(1, 10),
            'received_quantity' => 0,
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice,
        ];
    }
}
