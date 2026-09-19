<?php

namespace Database\Factories;

use App\Models\InboundReceipt;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InboundReceipt> */
class InboundReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'receipt_code' => 'WGR-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'purchase_order_id' => PurchaseOrder::factory(),
            'warehouse_id' => Warehouse::factory(),
            'location_id' => WarehouseLocation::factory(),
            'receipt_date' => now()->toDateString(),
            'status' => 'posted',
            'total' => 0,
        ];
    }
}
