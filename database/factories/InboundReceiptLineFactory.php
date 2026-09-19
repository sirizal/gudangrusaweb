<?php

namespace Database\Factories;

use App\Models\InboundReceipt;
use App\Models\InboundReceiptLine;
use App\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InboundReceiptLine> */
class InboundReceiptLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inbound_receipt_id' => InboundReceipt::factory(),
            'purchase_order_line_id' => PurchaseOrderLine::factory(),
            'quantity_received' => 1,
            'unit_cost' => fake()->numberBetween(1000, 100000),
            'line_cost' => 0,
        ];
    }
}
