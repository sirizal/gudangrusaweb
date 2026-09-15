<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'invoice_code',
    'sales_order_id',
    'customer_id',
    'invoice_date',
    'due_date',
    'payment_term_id',
    'status',
    'subtotal',
    'discount_amount',
    'tax_amount',
    'total',
    'paid_amount',
    'journal_entry_id',
    'notes',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use AuditsActivity, Blameable, HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => 'issued',
        'subtotal' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 0,
        'paid_amount' => 0,
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('payment_date');
    }

    /**
     * Days since the invoice was created.
     */
    public function getAgeDaysAttribute(): int
    {
        return (int) $this->invoice_date->diffInDays(now(), false);
    }

    /**
     * Days overdue (0 when not yet due or fully paid).
     */
    public function getOverdueDaysAttribute(): int
    {
        if ($this->isFullyPaid() || $this->status === InvoiceStatus::Cancelled) {
            return 0;
        }

        return max(0, (int) $this->due_date->diffInDays(now(), false));
    }

    /**
     * The aging bucket label, or null when not overdue.
     */
    public function getAgingBucketAttribute(): ?string
    {
        $days = $this->overdue_days;

        if ($days <= 0) {
            return null;
        }

        return match (true) {
            $days <= 30 => '1-30 days',
            $days <= 60 => '31-60 days',
            $days <= 90 => '61-90 days',
            default => '90+ days',
        };
    }

    public function isFullyPaid(): bool
    {
        return (float) $this->paid_amount >= (float) $this->total;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }
}
