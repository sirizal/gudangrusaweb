<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\CashFlowActivity;
use App\Enums\NormalBalance;
use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'company_id',
    'parent_id',
    'account_code',
    'account_name',
    'account_name_en',
    'account_type',
    'account_sub_type',
    'normal_balance',
    'level',
    'is_group',
    'is_postable',
    'is_active',
    'financial_statement',
    'financial_statement_line',
    'cash_flow_category',
    'cash_flow_activity',
    'description',
    'created_by',
    'updated_by',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = [
        'is_group' => false,
        'is_postable' => true,
        'is_active' => true,
        'level' => 1,
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('account_code');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Scope a query to only include accounts that can receive postings.
     */
    public function scopePostable(Builder $query): Builder
    {
        return $query->where('is_postable', true)->where('is_active', true);
    }

    /**
     * Scope a query to only include active accounts.
     */
    public function scopeActiveAccount(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include group accounts.
     */
    public function scopeGroup(Builder $query): Builder
    {
        return $query->where('is_group', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
            'normal_balance' => NormalBalance::class,
            'cash_flow_activity' => CashFlowActivity::class,
            'level' => 'integer',
            'is_group' => 'boolean',
            'is_postable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
