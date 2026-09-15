<?php

namespace App\Models;

use Database\Factories\BudgetLineMonthFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'budget_line_id',
    'month',
    'amount',
])]
class BudgetLineMonth extends Model
{
    /** @use HasFactory<BudgetLineMonthFactory> */
    use HasFactory;

    protected $attributes = [
        'amount' => 0,
    ];

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'amount' => 'decimal:2',
        ];
    }
}
