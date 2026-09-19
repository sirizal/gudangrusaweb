<?php

namespace App\Models;

use Database\Factories\VendorAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vendor_id', 'address_code', 'label', 'address', 'country_id', 'province_id', 'district_id',
    'sub_district_id', 'village_id', 'postal_code', 'phone', 'is_default',
])]
class VendorAddress extends Model
{
    /** @use HasFactory<VendorAddressFactory> */
    use HasFactory;

    protected $attributes = ['is_default' => false];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }
}
