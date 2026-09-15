<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\AccountingPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'fiscal_year_id',
    'period_number',
    'period_name',
    'start_date',
    'end_date',
    'status',
])]
class AccountingPeriod extends Model
{
    /** @use HasFactory<AccountingPeriodFactory> */
    use AuditsActivity, Blameable, HasFactory;

    protected $attributes = [
        'status' => 'open',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Scope a query to only include open periods.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', PeriodStatus::Open);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => PeriodStatus::class,
        ];
    }
}
