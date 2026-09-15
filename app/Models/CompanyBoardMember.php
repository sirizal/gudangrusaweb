<?php

namespace App\Models;

use App\Enums\CompanyBoardPosition;
use Database\Factories\CompanyBoardMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'name',
    'position',
    'nik',
    'start_date',
    'end_date',
    'is_active',
])]
class CompanyBoardMember extends Model
{
    /** @use HasFactory<CompanyBoardMemberFactory> */
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => CompanyBoardPosition::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
