<?php

namespace App\Models;

use App\Enums\VendorBillStatus;
use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\VendorBillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'bill_code', 'company_id', 'vendor_id', 'purchase_order_id', 'bill_date', 'due_date',
    'payment_term_id', 'status', 'subtotal', 'discount_amount', 'tax_amount', 'total',
    'paid_amount', 'journal_entry_id', 'notes',
])]
class VendorBill extends Model
{
    /** @use HasFactory<VendorBillFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = ['status' => 'draft', 'subtotal' => 0, 'discount_amount' => 0, 'tax_amount' => 0, 'total' => 0, 'paid_amount' => 0];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPaymentLine::class);
    }

    public function isFullyPaid(): bool
    {
        return (float) $this->paid_amount >= (float) $this->total;
    }

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'status' => VendorBillStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }
}
