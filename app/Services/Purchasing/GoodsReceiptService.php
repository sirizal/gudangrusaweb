<?php

namespace App\Services\Purchasing;

use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Services\Accounting\JournalNumberGenerator;
use App\Services\Purchasing\Concerns\PostsPurchasingJournals;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GoodsReceiptService
{
    use PostsPurchasingJournals;

    public function __construct(
        private readonly PurchasingNumberGenerator $numbers,
        private readonly PurchaseOrderService $orders,
        private readonly JournalNumberGenerator $journalNumbers,
    ) {}

    /**
     * Receive general and capex lines of a purchase order. Inventory lines are
     * received by the warehouse management system and are ignored here.
     *
     * @param  array<string, mixed>  $data
     */
    public function receive(PurchaseOrder $order, array $data, ?User $actor = null): GoodsReceipt
    {
        if (in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Cancelled, PurchaseOrderStatus::Closed], true)) {
            throw new InvalidArgumentException('Purchase order '.$order->po_code.' cannot be received in its current status.');
        }

        $receiptDate = $data['receipt_date'] ?? now()->toDateString();
        $lines = $data['lines'] ?? [];

        $order->load('lines');

        $selected = [];

        foreach ($lines as $line) {
            $quantity = (int) ($line['quantity_received'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            /** @var PurchaseOrderLine|null $poLine */
            $poLine = $order->lines->firstWhere('id', $line['purchase_order_line_id'] ?? null);

            if (! $poLine) {
                throw new InvalidArgumentException('A selected receipt line does not belong to this purchase order.');
            }

            if (! $poLine->purchase_type->isReceivedHere()) {
                throw new InvalidArgumentException('Inventory lines ('.$poLine->product?->name.') are received by the warehouse, not on this panel.');
            }

            $remaining = $poLine->quantity - $poLine->received_quantity;

            if ($quantity > $remaining) {
                throw new InvalidArgumentException('Received quantity for a line exceeds the remaining quantity of '.$remaining.'.');
            }

            $selected[] = [$poLine, $quantity];
        }

        if ($selected === []) {
            throw new InvalidArgumentException('There are no lines to receive.');
        }

        return DB::transaction(function () use ($order, $receiptDate, $data, $selected, $actor): GoodsReceipt {
            $receipt = GoodsReceipt::create([
                'receipt_code' => $this->numbers->nextReceiptCode(Carbon::parse($receiptDate)),
                'purchase_order_id' => $order->id,
                'receipt_date' => $receiptDate,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
            ]);

            $grni = $this->resolveAccount('BS-GRNI', $order->company_id);
            $journalLines = [];
            $total = 0.0;

            foreach ($selected as [$poLine, $quantity]) {
                $amount = round($quantity * (float) $poLine->unit_price, 2);
                $total += $amount;

                $receipt->lines()->create([
                    'purchase_order_line_id' => $poLine->id,
                    'quantity_received' => $quantity,
                ]);

                $poLine->update(['received_quantity' => $poLine->received_quantity + $quantity]);

                $debitAccountId = $poLine->account_id;

                if (! $debitAccountId) {
                    throw new InvalidArgumentException('A general/CAPEX purchase line must have an account before it can be received.');
                }

                $journalLines[] = [
                    'account_id' => $debitAccountId,
                    'description' => 'Goods received '.$receipt->receipt_code,
                    'debit' => $amount,
                    'credit' => 0,
                ];
            }

            $journalLines[] = [
                'account_id' => $grni->id,
                'description' => 'GRNI '.$receipt->receipt_code,
                'debit' => 0,
                'credit' => $total,
            ];

            $journal = $this->postJournal($journalLines, [
                'company_id' => $order->company_id,
                'date' => $receiptDate,
                'description' => 'Goods receipt '.$receipt->receipt_code,
                'source' => 'purchasing_receipt',
                'reference_type' => GoodsReceipt::class,
                'reference_id' => $receipt->id,
            ], $actor);

            $receipt->update(['total' => $total, 'journal_entry_id' => $journal->id]);

            $this->orders->recomputeStatus($order->fresh());

            $receipt->recordAudit('goods_receipt_posted', ['receipt_code' => $receipt->receipt_code, 'journal_number' => $journal->journal_number]);

            return $receipt->fresh();
        });
    }
}
