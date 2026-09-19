<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GoodsReceipt> */
class GoodsReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'receipt_code' => 'GR-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'purchase_order_id' => PurchaseOrder::factory(),
            'receipt_date' => now()->toDateString(),
            'status' => 'posted',
            'total' => 0,
        ];
    }
}
