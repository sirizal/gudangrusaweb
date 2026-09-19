<?php

namespace Database\Factories;

use App\Models\OutboundShipment;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OutboundShipment> */
class OutboundShipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_code' => 'WSO-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'sales_order_id' => SalesOrder::factory(),
            'warehouse_id' => Warehouse::factory(),
            'location_id' => WarehouseLocation::factory(),
            'shipment_date' => now()->toDateString(),
            'status' => 'posted',
            'total_cost' => 0,
        ];
    }
}
