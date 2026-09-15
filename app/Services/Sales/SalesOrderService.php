<?php

namespace App\Services\Sales;

use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesOrderService
{
    public function __construct(
        private readonly SalesNumberGenerator $numbers,
        private readonly SalesInvoiceService $invoices,
    ) {}

    /**
     * Create a sales order with its lines.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): SalesOrder
    {
        $lines = $this->normalizeLines($data['lines'] ?? []);

        if ($lines === []) {
            throw new InvalidArgumentException('An order must have at least one line.');
        }

        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);

        return DB::transaction(function () use ($data, $lines, $subtotal): SalesOrder {
            $order = SalesOrder::create([
                'order_code' => $this->numbers->nextOrderCode(isset($data['order_date']) ? Carbon::parse($data['order_date']) : null),
                'company_id' => $data['company_id'],
                'customer_id' => $data['customer_id'],
                'billing_address_id' => $data['billing_address_id'] ?? null,
                'shipping_address_id' => $data['shipping_address_id'] ?? null,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'payment_term_id' => $data['payment_term_id'] ?? $this->defaultPaymentTerm($data['customer_id']),
                'status' => SalesOrderStatus::Raised,
                'subtotal' => $subtotal,
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'tax_amount' => 0,
                'total' => $subtotal - (float) ($data['discount_amount'] ?? 0),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $order->lines()->create($line);
            }

            $order->recordAudit('sales_order_created', ['order_code' => $order->order_code]);

            return $order->load('lines');
        });
    }

    /**
     * Update a raised order (header and lines) and recompute totals.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SalesOrder $order, array $data, ?User $actor = null): SalesOrder
    {
        if ($order->status !== SalesOrderStatus::Raised) {
            throw new InvalidArgumentException('Only raised orders can be edited.');
        }

        $lines = $this->normalizeLines($data['lines'] ?? []);

        if ($lines === []) {
            throw new InvalidArgumentException('An order must have at least one line.');
        }

        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);

        return DB::transaction(function () use ($order, $data, $lines, $subtotal): SalesOrder {
            $order->update([
                'customer_id' => $data['customer_id'],
                'billing_address_id' => $data['billing_address_id'] ?? null,
                'shipping_address_id' => $data['shipping_address_id'] ?? null,
                'order_date' => $data['order_date'] ?? $order->order_date->toDateString(),
                'payment_term_id' => $data['payment_term_id'] ?? $order->payment_term_id,
                'subtotal' => $subtotal,
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'total' => $subtotal - (float) ($data['discount_amount'] ?? 0),
                'notes' => $data['notes'] ?? null,
            ]);

            $order->lines()->delete();

            foreach ($lines as $line) {
                $order->lines()->create($line);
            }

            $order->recordAudit('sales_order_updated', ['order_code' => $order->order_code]);

            return $order->fresh()->load('lines');
        });
    }

    /**
     * Advance an order to its next status.
     */
    public function advance(SalesOrder $order, ?User $actor = null): SalesOrder
    {
        $next = $order->status->next();

        if ($next === []) {
            throw new InvalidArgumentException('Order '.$order->order_code.' cannot advance from "'.$order->status->getLabel().'".');
        }

        return DB::transaction(function () use ($order, $next, $actor): SalesOrder {
            $target = $next[0];

            $order->update([
                'status' => $target,
                'delivered_at' => $target === SalesOrderStatus::Delivered ? now() : $order->delivered_at,
            ]);

            $order->recordAudit('sales_order_status', [
                'order_code' => $order->order_code,
                'status' => $target->value,
            ]);

            if ($target === SalesOrderStatus::Delivered) {
                $this->invoices->issueFromOrder($order->fresh(), $actor);
            }

            return $order->fresh()->load('lines');
        });
    }

    /**
     * Cancel an order that has not been delivered.
     */
    public function cancel(SalesOrder $order, ?User $actor = null): SalesOrder
    {
        if ($order->status->isTerminal()) {
            throw new InvalidArgumentException('Order '.$order->order_code.' is already '.$order->status->getLabel().'.');
        }

        $order->update(['status' => SalesOrderStatus::Cancelled]);
        $order->recordAudit('sales_order_cancelled', ['order_code' => $order->order_code]);

        return $order->fresh();
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

            if ($unitPrice < 0) {
                throw new InvalidArgumentException('Line unit price must not be negative.');
            }

            $normalized[] = [
                'product_id' => $line['product_id'] ?? null,
                'description' => $line['description'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
            ];
        }

        return $normalized;
    }

    protected function defaultPaymentTerm(int $customerId): ?int
    {
        $customer = Customer::find($customerId);

        return $customer?->payment_term_id;
    }
}
