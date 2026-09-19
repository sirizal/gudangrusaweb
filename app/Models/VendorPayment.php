<?php

namespace App\Models;

use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\VendorPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'payment_code', 'company_id', 'vendor_id', 'payment_date', 'total', 'reference', 'status',
    'journal_entry_id', 'notes',
])]
class VendorPayment extends Model
{
    /** @use HasFactory<VendorPaymentFactory> */
    use AuditsActivity, Blameable, HasFactory;

    protected $attributes = ['status' => 'posted', 'total' => 0];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VendorPaymentLine::class)->orderBy('id');
    }

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'total' => 'decimal:2'];
    }
}
