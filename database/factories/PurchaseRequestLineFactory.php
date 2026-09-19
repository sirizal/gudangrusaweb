<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseRequestLine> */
class PurchaseRequestLineFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(10000, 500000);

        return [
            'purchase_request_id' => PurchaseRequest::factory(),
            'purchase_type' => 'inventory',
            'product_id' => Product::factory(),
            'description' => fake()->words(4, true),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice,
        ];
    }
}
