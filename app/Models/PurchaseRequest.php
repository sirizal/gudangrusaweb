<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\PurchaseRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'request_code', 'company_id', 'vendor_id', 'request_date', 'needed_date', 'status', 'total',
    'notes', 'requested_by', 'approved_by', 'approved_at',
])]
class PurchaseRequest extends Model
{
    /** @use HasFactory<PurchaseRequestFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = ['status' => 'draft', 'total' => 0];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequestLine::class)->orderBy('id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'needed_date' => 'date',
            'status' => PurchaseRequestStatus::class,
            'total' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }
}
