<?php

namespace App\Models;

use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'warehouse_code', 'name', 'company_id', 'address', 'country_id', 'province_id', 'district_id',
    'sub_district_id', 'village_id', 'postal_code', 'phone', 'is_active',
])]
class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = ['is_active' => true];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function subDistrict(): BelongsTo
    {
        return $this->belongsTo(SubDistrict::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class)->orderBy('location_code');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
