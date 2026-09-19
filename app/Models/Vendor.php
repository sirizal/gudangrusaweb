<?php

namespace App\Models;

use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'vendor_code', 'name', 'email', 'phone', 'website', 'npwp', 'is_pkp', 'payment_term_id', 'is_active',
])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = ['is_active' => true, 'is_pkp' => false];

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(VendorAddress::class)->orderBy('address_code');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    protected function casts(): array
    {
        return ['is_pkp' => 'boolean', 'is_active' => 'boolean'];
    }
}
