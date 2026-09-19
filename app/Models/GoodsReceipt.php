<?php

namespace App\Models;

use App\Models\Concerns\AuditsActivity;
use App\Models\Concerns\Blameable;
use Database\Factories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'receipt_code', 'purchase_order_id', 'receipt_date', 'status', 'total', 'notes', 'journal_entry_id',
])]
class GoodsReceipt extends Model
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use AuditsActivity, Blameable, HasFactory;

    protected $attributes = ['status' => 'posted', 'total' => 0];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class)->orderBy('id');
    }

    protected function casts(): array
    {
        return ['receipt_date' => 'date', 'total' => 'decimal:2'];
    }
}
