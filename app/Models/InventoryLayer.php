<?php

namespace App\Models;

use Database\Factories\InventoryLayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id', 'warehouse_id', 'location_id', 'quantity_received', 'quantity_remaining',
    'unit_cost', 'received_date', 'reference_type', 'reference_id',
])]
class InventoryLayer extends Model
{
    /** @use HasFactory<InventoryLayerFactory> */
    use HasFactory;

    protected $attributes = ['quantity_received' => 0, 'quantity_remaining' => 0, 'unit_cost' => 0];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    protected function casts(): array
    {
        return [
            'quantity_received' => 'integer',
            'quantity_remaining' => 'integer',
            'unit_cost' => 'decimal:2',
            'received_date' => 'date',
        ];
    }
}
