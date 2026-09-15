<?php

namespace App\Models;

use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code',
    'name',
    'is_active',
    'address',
    'postal_code',
    'phone',
    'email',
    'website',
    'country_id',
    'province_id',
    'district_id',
    'sub_district_id',
    'village_id',
    'npwp',
    'nib',
    'is_pkp',
    'tax_office',
    'tax_registration_date',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = [
        'is_active' => true,
        'is_pkp' => false,
    ];

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

    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class)->orderBy('start_date');
    }

    public function boardMembers(): HasMany
    {
        return $this->hasMany(CompanyBoardMember::class)->orderBy('start_date');
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
            'is_pkp' => 'boolean',
            'tax_registration_date' => 'date',
        ];
    }
}
