<?php

namespace App\Services\Wms;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\WarehouseLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockTransferService
{
    public function __construct(
        private readonly WmsNumberGenerator $numbers,
        private readonly FifoService $fifo,
    ) {}

    /**
     * Transfer stock between two locations of a warehouse, preserving FIFO cost.
     *
     * @param  array<string, mixed>  $data
     */
    public function transfer(array $data, ?User $actor = null): StockTransfer
    {
        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        $fromId = (int) ($data['from_location_id'] ?? 0);
        $toId = (int) ($data['to_location_id'] ?? 0);

        if (! $warehouseId || ! $fromId || ! $toId) {
            throw new InvalidArgumentException('A warehouse and both locations are required.');
        }

        if ($fromId === $toId) {
            throw new InvalidArgumentException('The source and destination locations must be different.');
        }

        $this->assertSameWarehouse($warehouseId, [$fromId, $toId]);

        $transferDate = $data['transfer_date'] ?? now()->toDateString();

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $line): bool => (int) ($line['quantity'] ?? 0) > 0)
            ->values();

        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('There are no lines to transfer.');
        }

        return DB::transaction(function () use ($warehouseId, $fromId, $toId, $transferDate, $data, $lines): StockTransfer {
            $transfer = StockTransfer::create([
                'transfer_code' => $this->numbers->nextTransferCode(Carbon::parse($transferDate)),
                'warehouse_id' => $warehouseId,
                'from_location_id' => $fromId,
                'to_location_id' => $toId,
                'transfer_date' => $transferDate,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $product = Product::findOrFail($line['product_id']);
                $quantity = (int) $line['quantity'];

                $result = $this->fifo->consume($product, $fromId, $quantity);
                $this->fifo->stock($product->id, $warehouseId, $fromId, -$quantity);

                // Recreate the consumed layers at the destination to preserve FIFO cost.
                // receive() also increases the destination stock quantity.
                foreach ($result['layers'] as $chunk) {
                    $this->fifo->receive(
                        $product,
                        $warehouseId,
                        $toId,
                        $chunk['quantity'],
                        (float) $chunk['unit_cost'],
                        $transferDate,
                        StockTransfer::class,
                        $transfer->id,
                    );
                }

                $transfer->lines()->create(['product_id' => $product->id, 'quantity' => $quantity]);

                foreach ([[StockMovementType::TransferOut, $fromId, -$quantity], [StockMovementType::TransferIn, $toId, $quantity]] as [$type, $locationId, $signed]) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouseId,
                        'location_id' => $locationId,
                        'type' => $type,
                        'quantity' => $signed,
                        'unit_cost' => $quantity > 0 ? round($result['cost'] / $quantity, 2) : 0,
                        'total_cost' => $result['cost'],
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'movement_date' => $transferDate,
                    ]);
                }
            }

            $transfer->recordAudit('stock_transfer_posted', ['transfer_code' => $transfer->transfer_code]);

            return $transfer->fresh();
        });
    }

    /**
     * @param  array<int, int>  $locationIds
     */
    protected function assertSameWarehouse(int $warehouseId, array $locationIds): void
    {
        $count = WarehouseLocation::whereIn('id', $locationIds)->where('warehouse_id', $warehouseId)->count();

        if ($count !== count($locationIds)) {
            throw new InvalidArgumentException('Both locations must belong to the selected warehouse.');
        }
    }
}
