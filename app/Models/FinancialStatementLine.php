<?php

namespace App\Models;

use App\Enums\StatementType;
use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\FinancialStatementLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'statement_type',
    'code',
    'name',
    'sequence',
    'is_active',
])]
class FinancialStatementLine extends Model
{
    /** @use HasFactory<FinancialStatementLineFactory> */
    use AuditsActivity, Blameable, HasFactory;

    protected $attributes = [
        'sequence' => 0,
        'is_active' => true,
    ];

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'financial_statement_line_accounts')
            ->withPivot('include_descendants');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'statement_type' => StatementType::class,
            'sequence' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
