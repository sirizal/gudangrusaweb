<?php

namespace App\Services\Wms;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\InboundReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Accounting\JournalNumberGenerator;
use App\Services\Concerns\PostsJournals;
use App\Services\Purchasing\PurchaseOrderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InboundService
{
    use PostsJournals;

    public function __construct(
        private readonly WmsNumberGenerator $numbers,
        private readonly FifoService $fifo,
        private readonly PurchaseOrderService $orders,
        private readonly JournalNumberGenerator $journalNumbers,
    ) {}

    /**
     * Receive inventory lines of a purchase order into a warehouse location.
     *
     * @param  array<string, mixed>  $data
     */
    public function receive(PurchaseOrder $order, array $data, ?User $actor = null): InboundReceipt
    {
        if (in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Cancelled, PurchaseOrderStatus::Closed], true)) {
            throw new InvalidArgumentException('Purchase order '.$order->po_code.' cannot be received in its current status.');
        }

        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        $locationId = (int) ($data['location_id'] ?? 0);

        if (! $warehouseId || ! $locationId) {
            throw new InvalidArgumentException('A warehouse and location are required.');
        }

        $receiptDate = $data['receipt_date'] ?? now()->toDateString();
        $order->load('lines');

        $selected = [];

        foreach ($data['lines'] ?? [] as $line) {
            $quantity = (int) ($line['quantity_received'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            /** @var PurchaseOrderLine|null $poLine */
            $poLine = $order->lines->firstWhere('id', $line['purchase_order_line_id'] ?? null);

            if (! $poLine) {
                throw new InvalidArgumentException('A selected line does not belong to this purchase order.');
            }

            if ($poLine->purchase_type->isReceivedHere()) {
                throw new InvalidArgumentException('Only inventory lines are received by the warehouse; general/CAPEX lines are received on the purchasing panel.');
            }

            if (! $poLine->product_id) {
                throw new InvalidArgumentException('An inventory purchase line must reference a product.');
            }

            $remaining = $poLine->quantity - $poLine->received_quantity;

            if ($quantity > $remaining) {
                throw new InvalidArgumentException('Received quantity exceeds the remaining quantity of '.$remaining.'.');
            }

            $selected[] = [$poLine, $quantity];
        }

        if ($selected === []) {
            throw new InvalidArgumentException('There are no lines to receive.');
        }

        return DB::transaction(function () use ($order, $warehouseId, $locationId, $receiptDate, $data, $selected, $actor): InboundReceipt {
            $receipt = InboundReceipt::create([
                'receipt_code' => $this->numbers->nextInboundCode(Carbon::parse($receiptDate)),
                'purchase_order_id' => $order->id,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'receipt_date' => $receiptDate,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
            ]);

            $total = 0.0;

            foreach ($selected as [$poLine, $quantity]) {
                $unitCost = (float) $poLine->unit_price;
                $lineCost = round($quantity * $unitCost, 2);
                $total += $lineCost;

                $this->fifo->receive(
                    $poLine->product,
                    $warehouseId,
                    $locationId,
                    $quantity,
                    $unitCost,
                    $receiptDate,
                    InboundReceipt::class,
                    $receipt->id,
                );

                $receipt->lines()->create([
                    'purchase_order_line_id' => $poLine->id,
                    'quantity_received' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_cost' => $lineCost,
                ]);

                StockMovement::create([
                    'product_id' => $poLine->product_id,
                    'warehouse_id' => $warehouseId,
                    'location_id' => $locationId,
                    'type' => StockMovementType::Inbound,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                    'reference_type' => InboundReceipt::class,
                    'reference_id' => $receipt->id,
                    'movement_date' => $receiptDate,
                ]);

                $poLine->update(['received_quantity' => $poLine->received_quantity + $quantity]);
            }

            $inventory = $this->resolveAccount('BS-INV', $order->company_id);
            $grni = $this->resolveAccount('BS-GRNI', $order->company_id);

            $journal = $this->postJournal([
                ['account_id' => $inventory->id, 'description' => 'Inventory received '.$receipt->receipt_code, 'debit' => $total, 'credit' => 0],
                ['account_id' => $grni->id, 'description' => 'GRNI '.$receipt->receipt_code, 'debit' => 0, 'credit' => $total],
            ], [
                'company_id' => $order->company_id,
                'date' => $receiptDate,
                'description' => 'Warehouse inbound '.$receipt->receipt_code,
                'source' => 'wms_inbound',
                'reference_type' => InboundReceipt::class,
                'reference_id' => $receipt->id,
            ], $actor);

            $receipt->update(['total' => $total, 'journal_entry_id' => $journal->id]);

            $this->orders->recomputeStatus($order->fresh());

            $receipt->recordAudit('wms_inbound_posted', ['receipt_code' => $receipt->receipt_code, 'journal_number' => $journal->journal_number]);

            return $receipt->fresh();
        });
    }
}
