<?php

namespace App\Models;

use App\Enums\JournalStatus;
use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

#[Fillable([
    'company_id',
    'journal_number',
    'journal_date',
    'accounting_period_id',
    'reference_type',
    'reference_id',
    'description',
    'status',
    'source',
    'reversed_journal_id',
    'is_reversed',
    'posted_at',
    'posted_by',
    'approved_at',
    'approved_by',
    'created_by',
    'updated_by',
])]
class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => 'draft',
        'is_reversed' => false,
    ];

    protected static function booted(): void
    {
        static::updating(function (JournalEntry $entry): void {
            if (in_array($entry->getRawOriginal('status'), [JournalStatus::Posted->value, JournalStatus::Reversed->value], true)) {
                throw new LogicException('Posted journals cannot be modified.');
            }
        });

        static::deleting(function (JournalEntry $entry): void {
            if (in_array($entry->getRawOriginal('status'), [JournalStatus::Posted->value, JournalStatus::Reversed->value], true)) {
                throw new LogicException('Posted journals cannot be deleted.');
            }
        });
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_journal_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_journal_id');
    }

    /**
     * Scope a query to only include journals that affect financial statements.
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'status' => JournalStatus::class,
            'is_reversed' => 'boolean',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }
}
