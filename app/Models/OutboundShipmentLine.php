<?php

namespace App\Models;

use Database\Factories\OutboundShipmentLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['outbound_shipment_id', 'sales_order_line_id', 'quantity_shipped', 'cost'])]
class OutboundShipmentLine extends Model
{
    /** @use HasFactory<OutboundShipmentLineFactory> */
    use HasFactory;

    protected $attributes = ['quantity_shipped' => 0, 'cost' => 0];

    public function outboundShipment(): BelongsTo
    {
        return $this->belongsTo(OutboundShipment::class);
    }

    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class);
    }

    protected function casts(): array
    {
        return ['quantity_shipped' => 'integer', 'cost' => 'decimal:2'];
    }
}
