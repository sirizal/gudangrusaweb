<?php

namespace App\Models;

use Database\Factories\StockAdjustmentLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['stock_adjustment_id', 'product_id', 'quantity_delta', 'unit_cost', 'cost'])]
class StockAdjustmentLine extends Model
{
    /** @use HasFactory<StockAdjustmentLineFactory> */
    use HasFactory;

    protected $attributes = ['quantity_delta' => 0, 'unit_cost' => 0, 'cost' => 0];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return ['quantity_delta' => 'integer', 'unit_cost' => 'decimal:2', 'cost' => 'decimal:2'];
    }
}
