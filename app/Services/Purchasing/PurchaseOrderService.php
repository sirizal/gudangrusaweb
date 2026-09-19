<?php

namespace App\Services\Purchasing;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseOrderService
{
    public function __construct(
        private readonly PurchasingNumberGenerator $numbers,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): PurchaseOrder
    {
        $lines = $this->normalizeLines($data['lines'] ?? []);

        if ($lines === []) {
            throw new InvalidArgumentException('A purchase order must have at least one line.');
        }

        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
        $discount = (float) ($data['discount_amount'] ?? 0);

        return DB::transaction(function () use ($data, $lines, $subtotal, $discount): PurchaseOrder {
            $order = PurchaseOrder::create([
                'po_code' => $this->numbers->nextOrderCode(isset($data['po_date']) ? Carbon::parse($data['po_date']) : null),
                'company_id' => $data['company_id'],
                'vendor_id' => $data['vendor_id'],
                'purchase_request_id' => $data['purchase_request_id'] ?? null,
                'po_date' => $data['po_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'status' => PurchaseOrderStatus::Draft,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => 0,
                'total' => $subtotal - $discount,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $order->lines()->create($line);
            }

            $order->recordAudit('purchase_order_created', ['po_code' => $order->po_code]);

            return $order->load('lines');
        });
    }

    public function update(PurchaseOrder $order, array $data, ?User $actor = null): PurchaseOrder
    {
        if (! $order->status->isEditable()) {
            throw new InvalidArgumentException('Only draft purchase orders can be edited.');
        }

        $lines = $this->normalizeLines($data['lines'] ?? []);

        if ($lines === []) {
            throw new InvalidArgumentException('A purchase order must have at least one line.');
        }

        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
        $discount = (float) ($data['discount_amount'] ?? 0);

        return DB::transaction(function () use ($order, $data, $lines, $subtotal, $discount): PurchaseOrder {
            $order->update([
                'vendor_id' => $data['vendor_id'],
                'po_date' => $data['po_date'] ?? $order->po_date->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total' => $subtotal - $discount,
                'notes' => $data['notes'] ?? null,
            ]);

            $order->lines()->delete();

            foreach ($lines as $line) {
                $order->lines()->create($line);
            }

            return $order->fresh()->load('lines');
        });
    }

    public function confirm(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== PurchaseOrderStatus::Draft) {
            throw new InvalidArgumentException('Only draft purchase orders can be confirmed.');
        }

        $order->update(['status' => PurchaseOrderStatus::Open]);
        $order->recordAudit('purchase_order_confirmed', ['po_code' => $order->po_code]);

        return $order->fresh();
    }

    public function close(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status->isTerminal()) {
            throw new InvalidArgumentException('Purchase order '.$order->po_code.' is already '.$order->status->getLabel().'.');
        }

        $order->update(['status' => PurchaseOrderStatus::Closed]);
        $order->recordAudit('purchase_order_closed', ['po_code' => $order->po_code]);

        return $order->fresh();
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        if (in_array($order->status, [PurchaseOrderStatus::Received, PurchaseOrderStatus::Closed], true)) {
            throw new InvalidArgumentException('A received or closed purchase order cannot be cancelled.');
        }

        $order->update(['status' => PurchaseOrderStatus::Cancelled]);
        $order->recordAudit('purchase_order_cancelled', ['po_code' => $order->po_code]);

        return $order->fresh();
    }

    /**
     * Recompute the order status from its line received quantities.
     */
    public function recomputeStatus(PurchaseOrder $order): void
    {
        if ($order->status->isTerminal()) {
            return;
        }

        $lines = $order->lines()->get();
        $anyReceived = $lines->contains(fn ($line): bool => $line->received_quantity > 0);
        $allReceived = $lines->every(fn ($line): bool => $line->received_quantity >= $line->quantity);

        $status = match (true) {
            $allReceived => PurchaseOrderStatus::Received,
            $anyReceived => PurchaseOrderStatus::PartiallyReceived,
            default => $order->status === PurchaseOrderStatus::Draft ? PurchaseOrderStatus::Draft : PurchaseOrderStatus::Open,
        };

        $order->update(['status' => $status]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeLines(array $lines): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $quantity = (int) ($line['quantity'] ?? 1);
            $unitPrice = (float) ($line['unit_price'] ?? 0);

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Line quantity must be greater than zero.');
            }

            $normalized[] = [
                'purchase_type' => $line['purchase_type'] ?? 'inventory',
                'product_id' => $line['product_id'] ?? null,
                'account_id' => $line['account_id'] ?? null,
                'description' => $line['description'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
            ];
        }

        return $normalized;
    }
}
