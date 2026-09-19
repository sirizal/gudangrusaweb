<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GoodsReceiptLine> */
class GoodsReceiptLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'purchase_order_line_id' => PurchaseOrderLine::factory(),
            'quantity_received' => 1,
        ];
    }
}
