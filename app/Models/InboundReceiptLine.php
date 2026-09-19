<?php

namespace App\Models;

use Database\Factories\InboundReceiptLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inbound_receipt_id', 'purchase_order_line_id', 'quantity_received', 'unit_cost', 'line_cost'])]
class InboundReceiptLine extends Model
{
    /** @use HasFactory<InboundReceiptLineFactory> */
    use HasFactory;

    protected $attributes = ['quantity_received' => 0, 'unit_cost' => 0, 'line_cost' => 0];

    public function inboundReceipt(): BelongsTo
    {
        return $this->belongsTo(InboundReceipt::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    protected function casts(): array
    {
        return ['quantity_received' => 'integer', 'unit_cost' => 'decimal:2', 'line_cost' => 'decimal:2'];
    }
}
