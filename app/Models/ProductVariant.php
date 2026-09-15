<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_id',
    'sku',
    'model_number',
    'size',
    'color',
    'image_path',
    'unit',
    'metadata',
    'customer_sku',
    'available_stock',
    'leadtime',
    'selling_price',
    'is_active',
    'supply_city',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'is_active' => true,
        'available_stock' => 0,
        'selling_price' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductVariant $variant): void {
            if (blank($variant->sku)) {
                $variant->sku = static::generateSku();
            }
        });
    }

    /**
     * Generate the next SKU in the format "S" followed by seven running digits.
     */
    public static function generateSku(): string
    {
        $max = static::withTrashed()
            ->where('sku', 'like', 'S%')
            ->pluck('sku')
            ->map(fn (string $sku): int => (int) substr($sku, 1))
            ->max() ?? 0;

        return 'S'.str_pad((string) ($max + 1), 7, '0', STR_PAD_LEFT);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to only include variants that are in stock.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('available_stock', '>', 0);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'selling_price' => 'decimal:2',
            'metadata' => 'array',
        ];
    }
}
