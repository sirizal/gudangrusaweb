<?php

namespace App\Models;

use Database\Factories\SalesOrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sales_order_id',
    'product_id',
    'description',
    'quantity',
    'unit_price',
    'line_total',
])]
class SalesOrderLine extends Model
{
    /** @use HasFactory<SalesOrderLineFactory> */
    use HasFactory;

    protected $attributes = [
        'quantity' => 1,
        'unit_price' => 0,
        'line_total' => 0,
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
