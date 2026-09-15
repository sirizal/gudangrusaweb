<?php

namespace App\Models;

use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'symbol', 'decimal_places', 'is_base_currency', 'is_active'])]
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use AuditsActivity, Blameable, HasFactory;

    protected $attributes = [
        'decimal_places' => 2,
        'is_base_currency' => false,
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_base_currency' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
