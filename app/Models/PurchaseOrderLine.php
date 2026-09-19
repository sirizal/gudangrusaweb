<?php

namespace App\Models;

use App\Enums\PurchaseType;
use Database\Factories\PurchaseOrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_order_id', 'purchase_type', 'product_id', 'account_id', 'description',
    'quantity', 'received_quantity', 'unit_price', 'line_total',
])]
class PurchaseOrderLine extends Model
{
    /** @use HasFactory<PurchaseOrderLineFactory> */
    use HasFactory;

    protected $attributes = ['purchase_type' => 'inventory', 'quantity' => 1, 'received_quantity' => 0, 'unit_price' => 0, 'line_total' => 0];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    protected function casts(): array
    {
        return [
            'purchase_type' => PurchaseType::class,
            'quantity' => 'integer',
            'received_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
