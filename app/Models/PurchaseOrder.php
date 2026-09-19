<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'po_code', 'company_id', 'vendor_id', 'purchase_request_id', 'po_date', 'expected_date',
    'payment_term_id', 'status', 'subtotal', 'discount_amount', 'tax_amount', 'total', 'notes',
])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = ['status' => 'draft', 'subtotal' => 0, 'discount_amount' => 0, 'tax_amount' => 0, 'total' => 0];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'expected_date' => 'date',
            'status' => PurchaseOrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }
}
