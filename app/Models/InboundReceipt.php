<?php

namespace App\Models;

use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\InboundReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'receipt_code', 'purchase_order_id', 'warehouse_id', 'location_id', 'receipt_date',
    'status', 'total', 'notes', 'journal_entry_id',
])]
class InboundReceipt extends Model
{
    /** @use HasFactory<InboundReceiptFactory> */
    use AuditsActivity, Blameable, HasFactory;

    protected $attributes = ['status' => 'posted', 'total' => 0];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InboundReceiptLine::class)->orderBy('id');
    }

    protected function casts(): array
    {
        return ['receipt_date' => 'date', 'total' => 'decimal:2'];
    }
}
