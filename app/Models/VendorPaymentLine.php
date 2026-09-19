<?php

namespace App\Models;

use Database\Factories\VendorPaymentLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vendor_payment_id', 'vendor_bill_id', 'amount'])]
class VendorPaymentLine extends Model
{
    /** @use HasFactory<VendorPaymentLineFactory> */
    use HasFactory;

    protected $attributes = ['amount' => 0];

    public function vendorPayment(): BelongsTo
    {
        return $this->belongsTo(VendorPayment::class);
    }

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
