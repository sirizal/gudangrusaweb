<?php

namespace App\Models;

use App\Enums\WarehouseLocationType;
use Database\Factories\WarehouseLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'warehouse_id', 'location_code', 'name', 'type', 'weight_capacity', 'volume_capacity', 'is_active',
])]
class WarehouseLocation extends Model
{
    /** @use HasFactory<WarehouseLocationFactory> */
    use HasFactory;

    protected $attributes = ['type' => 'storage', 'weight_capacity' => 0, 'volume_capacity' => 0, 'is_active' => true];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class, 'location_id');
    }

    protected function casts(): array
    {
        return [
            'type' => WarehouseLocationType::class,
            'weight_capacity' => 'decimal:2',
            'volume_capacity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
