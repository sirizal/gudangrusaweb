<?php

namespace App\Services\Purchasing;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseRequestService
{
    public function __construct(
        private readonly PurchasingNumberGenerator $numbers,
        private readonly PurchaseOrderService $orders,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): PurchaseRequest
    {
        $lines = $this->normalizeLines($data['lines'] ?? []);

        if ($lines === []) {
            throw new InvalidArgumentException('A purchase request must have at least one line.');
        }

        $total = round(array_sum(array_column($lines, 'line_total')), 2);

        return DB::transaction(function () use ($data, $lines, $total, $actor): PurchaseRequest {
            $request = PurchaseRequest::create([
                'request_code' => $this->numbers->nextRequestCode(isset($data['request_date']) ? Carbon::parse($data['request_date']) : null),
                'company_id' => $data['company_id'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'request_date' => $data['request_date'] ?? now()->toDateString(),
                'needed_date' => $data['needed_date'] ?? null,
                'status' => PurchaseRequestStatus::Draft,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'requested_by' => $actor?->id ?? auth()->id(),
            ]);

            foreach ($lines as $line) {
                $request->lines()->create($line);
            }

            $request->recordAudit('purchase_request_created', ['request_code' => $request->request_code]);

            return $request->load('lines');
        });
    }

    public function update(PurchaseRequest $request, array $data, ?User $actor = null): PurchaseRequest
    {
        if (! $request->status->isEditable()) {
            throw new InvalidArgumentException('Only draft or rejected purchase requests can be edited.');
        }

        $lines = $this->normalizeLines($data['lines'] ?? []);

        if ($lines === []) {
            throw new InvalidArgumentException('A purchase request must have at least one line.');
        }

        $total = round(array_sum(array_column($lines, 'line_total')), 2);

        return DB::transaction(function () use ($request, $data, $lines, $total): PurchaseRequest {
            $request->update([
                'vendor_id' => $data['vendor_id'] ?? null,
                'request_date' => $data['request_date'] ?? $request->request_date->toDateString(),
                'needed_date' => $data['needed_date'] ?? null,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            $request->lines()->delete();

            foreach ($lines as $line) {
                $request->lines()->create($line);
            }

            return $request->fresh()->load('lines');
        });
    }

    public function submit(PurchaseRequest $request): PurchaseRequest
    {
        if (! in_array($request->status, [PurchaseRequestStatus::Draft, PurchaseRequestStatus::Rejected], true)) {
            throw new InvalidArgumentException('Only draft or rejected requests can be submitted.');
        }

        $request->update(['status' => PurchaseRequestStatus::Submitted]);
        $request->recordAudit('purchase_request_submitted', ['request_code' => $request->request_code]);

        return $request->fresh();
    }

    public function approve(PurchaseRequest $request, ?User $actor = null): PurchaseRequest
    {
        if ($request->status !== PurchaseRequestStatus::Submitted) {
            throw new InvalidArgumentException('Only submitted requests can be approved.');
        }

        $request->update([
            'status' => PurchaseRequestStatus::Approved,
            'approved_by' => $actor?->id ?? auth()->id(),
            'approved_at' => now(),
        ]);
        $request->recordAudit('purchase_request_approved', ['request_code' => $request->request_code]);

        return $request->fresh();
    }

    public function reject(PurchaseRequest $request): PurchaseRequest
    {
        if ($request->status !== PurchaseRequestStatus::Submitted) {
            throw new InvalidArgumentException('Only submitted requests can be rejected.');
        }

        $request->update(['status' => PurchaseRequestStatus::Rejected]);
        $request->recordAudit('purchase_request_rejected', ['request_code' => $request->request_code]);

        return $request->fresh();
    }

    public function convertToOrder(PurchaseRequest $request, ?User $actor = null): PurchaseRequest
    {
        if ($request->status !== PurchaseRequestStatus::Approved) {
            throw new InvalidArgumentException('Only approved requests can be converted to a purchase order.');
        }

        if (! $request->vendor_id) {
            throw new InvalidArgumentException('A vendor must be set before converting to a purchase order.');
        }

        DB::transaction(function () use ($request, $actor): void {
            $order = $this->orders->create([
                'company_id' => $request->company_id,
                'vendor_id' => $request->vendor_id,
                'purchase_request_id' => $request->id,
                'po_date' => now()->toDateString(),
                'lines' => $request->lines->map(fn (PurchaseRequestLine $line): array => [
                    'purchase_type' => $line->purchase_type->value,
                    'product_id' => $line->product_id,
                    'account_id' => $line->account_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                ])->all(),
            ], $actor);

            $this->orders->confirm($order);
            $request->update(['status' => PurchaseRequestStatus::Converted]);
            $request->recordAudit('purchase_request_converted', ['request_code' => $request->request_code, 'po_code' => $order->po_code]);
        });

        return $request->fresh();
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
