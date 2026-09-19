<?php

namespace App\Models;

use Database\Factories\GoodsReceiptLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['goods_receipt_id', 'purchase_order_line_id', 'quantity_received'])]
class GoodsReceiptLine extends Model
{
    /** @use HasFactory<GoodsReceiptLineFactory> */
    use HasFactory;

    protected $attributes = ['quantity_received' => 0];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    protected function casts(): array
    {
        return ['quantity_received' => 'integer'];
    }
}
