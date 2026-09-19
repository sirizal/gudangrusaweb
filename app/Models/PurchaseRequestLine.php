<?php

namespace App\Models;

use App\Enums\PurchaseType;
use Database\Factories\PurchaseRequestLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_request_id', 'purchase_type', 'product_id', 'account_id', 'description',
    'quantity', 'unit_price', 'line_total',
])]
class PurchaseRequestLine extends Model
{
    /** @use HasFactory<PurchaseRequestLineFactory> */
    use HasFactory;

    protected $attributes = ['purchase_type' => 'inventory', 'quantity' => 1, 'unit_price' => 0, 'line_total' => 0];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
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
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
