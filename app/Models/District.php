<?php

namespace App\Models;

use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'province_id',
    'code',
    'name',
    'is_active',
])]
class District extends Model
{
    /** @use HasFactory<DistrictFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = [
        'is_active' => true,
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function subDistricts(): HasMany
    {
        return $this->hasMany(SubDistrict::class)->orderBy('name');
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
        ];
    }
}
