<?php

namespace App\Models;

use Database\Factories\PaymentTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'due_days',
    'is_active',
])]
class PaymentTerm extends Model
{
    /** @use HasFactory<PaymentTermFactory> */
    use HasFactory;

    protected $attributes = [
        'due_days' => 30,
        'is_active' => true,
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
