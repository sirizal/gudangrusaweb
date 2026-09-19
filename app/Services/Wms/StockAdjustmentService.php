<?php

namespace App\Services\Wms;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\JournalNumberGenerator;
use App\Services\Concerns\PostsJournals;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockAdjustmentService
{
    use PostsJournals;

    public function __construct(
        private readonly WmsNumberGenerator $numbers,
        private readonly FifoService $fifo,
        private readonly JournalNumberGenerator $journalNumbers,
    ) {}

    /**
     * Adjust stock at a location (positive delta = gain, negative = loss).
     *
     * @param  array<string, mixed>  $data
     */
    public function adjust(array $data, ?User $actor = null, ?int $companyId = null): StockAdjustment
    {
        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        $locationId = (int) ($data['location_id'] ?? 0);

        if (! $warehouseId || ! $locationId) {
            throw new InvalidArgumentException('A warehouse and location are required.');
        }

        $adjustmentDate = $data['adjustment_date'] ?? now()->toDateString();

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $line): bool => (int) ($line['quantity_delta'] ?? 0) !== 0)
            ->values();

        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('There are no adjustment lines.');
        }

        if (! $companyId) {
            $companyId = Warehouse::findOrFail($warehouseId)->company_id;
        }

        return DB::transaction(function () use ($warehouseId, $locationId, $adjustmentDate, $data, $lines, $actor, $companyId): StockAdjustment {
            $adjustment = StockAdjustment::create([
                'adjustment_code' => $this->numbers->nextAdjustmentCode(Carbon::parse($adjustmentDate)),
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'adjustment_date' => $adjustmentDate,
                'status' => 'posted',
                'reason' => $data['reason'] ?? null,
            ]);

            $totalCost = 0.0;

            foreach ($lines as $line) {
                $product = Product::findOrFail($line['product_id']);
                $delta = (int) $line['quantity_delta'];
                $unitCost = (float) ($line['unit_cost'] ?? 0);

                if ($delta > 0) {
                    $cost = round($delta * $unitCost, 2);
                    $this->fifo->receive($product, $warehouseId, $locationId, $delta, $unitCost, $adjustmentDate, StockAdjustment::class, $adjustment->id);
                    $type = StockMovementType::AdjustmentIn;
                } else {
                    $quantity = abs($delta);
                    $result = $this->fifo->consume($product, $locationId, $quantity);
                    $cost = $result['cost'];
                    $unitCost = $quantity > 0 ? round($cost / $quantity, 2) : 0;
                    $this->fifo->stock($product->id, $warehouseId, $locationId, -$quantity);
                    $type = StockMovementType::AdjustmentOut;
                }

                $totalCost += $delta > 0 ? $cost : -$cost;

                $adjustment->lines()->create([
                    'product_id' => $product->id,
                    'quantity_delta' => $delta,
                    'unit_cost' => $unitCost,
                    'cost' => $cost,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'location_id' => $locationId,
                    'type' => $type,
                    'quantity' => $delta,
                    'unit_cost' => $unitCost,
                    'total_cost' => $cost,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'movement_date' => $adjustmentDate,
                ]);
            }

            $journal = null;
            $netValue = round($totalCost, 2);

            if (abs($netValue) >= 0.01) {
                $inventory = $this->resolveAccount('BS-INV', $companyId);

                if ($netValue > 0) {
                    // Net gain: Dr Inventory / Cr adjustment income.
                    $income = $this->resolveAccount('IS-OI', $companyId);
                    $journalLines = [
                        ['account_id' => $inventory->id, 'description' => 'Stock adjustment '.$adjustment->adjustment_code, 'debit' => $netValue, 'credit' => 0],
                        ['account_id' => $income->id, 'description' => 'Stock adjustment '.$adjustment->adjustment_code, 'debit' => 0, 'credit' => $netValue],
                    ];
                } else {
                    // Net loss: Dr adjustment expense / Cr Inventory.
                    $expense = $this->resolveAccount('IS-OE', $companyId);
                    $loss = abs($netValue);
                    $journalLines = [
                        ['account_id' => $expense->id, 'description' => 'Stock adjustment '.$adjustment->adjustment_code, 'debit' => $loss, 'credit' => 0],
                        ['account_id' => $inventory->id, 'description' => 'Stock adjustment '.$adjustment->adjustment_code, 'debit' => 0, 'credit' => $loss],
                    ];
                }

                $journal = $this->postJournal($journalLines, [
                    'company_id' => $companyId,
                    'date' => $adjustmentDate,
                    'description' => 'Stock adjustment '.$adjustment->adjustment_code,
                    'source' => 'wms_adjustment',
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                ], $actor);

                $adjustment->update(['total_cost' => $netValue, 'journal_entry_id' => $journal->id]);
            }

            $adjustment->recordAudit('stock_adjustment_posted', ['adjustment_code' => $adjustment->adjustment_code, 'journal_number' => $journal?->journal_number]);

            return $adjustment->fresh();
        });
    }
}
