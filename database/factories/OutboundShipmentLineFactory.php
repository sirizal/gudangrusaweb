<?php

namespace Database\Factories;

use App\Models\OutboundShipment;
use App\Models\OutboundShipmentLine;
use App\Models\SalesOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OutboundShipmentLine> */
class OutboundShipmentLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'outbound_shipment_id' => OutboundShipment::factory(),
            'sales_order_line_id' => SalesOrderLine::factory(),
            'quantity_shipped' => 1,
            'cost' => 0,
        ];
    }
}
