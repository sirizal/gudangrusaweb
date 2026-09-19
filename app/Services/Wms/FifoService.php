<?php

namespace App\Services\Wms;

use App\Models\InventoryLayer;
use App\Models\InventoryStock;
use App\Models\Product;
use InvalidArgumentException;

class FifoService
{
    /**
     * Consume FIFO layers for a product at a location and return the total cost.
     *
     * @return array{cost: float, layers: array<int, array{layer_id: int, quantity: int, unit_cost: float}>}
     */
    public function consume(Product $product, int $locationId, int $quantity): array
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        $layers = InventoryLayer::query()
            ->where('product_id', $product->id)
            ->where('location_id', $locationId)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_date')
            ->orderBy('id')
            ->get();

        $available = (int) $layers->sum('quantity_remaining');

        if ($available < $quantity) {
            throw new InvalidArgumentException('Insufficient stock for '.$product->name.' at this location (available '.$available.').');
        }

        $needed = $quantity;
        $cost = 0.0;
        $consumed = [];

        foreach ($layers as $layer) {
            if ($needed <= 0) {
                break;
            }

            $take = min($needed, $layer->quantity_remaining);
            $layerCost = round($take * (float) $layer->unit_cost, 2);

            $layer->update(['quantity_remaining' => $layer->quantity_remaining - $take]);

            $cost += $layerCost;
            $consumed[] = ['layer_id' => $layer->id, 'quantity' => $take, 'unit_cost' => (float) $layer->unit_cost];
            $needed -= $take;
        }

        return ['cost' => round($cost, 2), 'layers' => $consumed];
    }

    /**
     * Add stock and create a FIFO layer.
     */
    public function receive(Product $product, int $warehouseId, int $locationId, int $quantity, float $unitCost, string $receivedDate, ?string $referenceType = null, ?int $referenceId = null): InventoryLayer
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        $this->stock($product->id, $warehouseId, $locationId, $quantity);

        return InventoryLayer::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'location_id' => $locationId,
            'quantity_received' => $quantity,
            'quantity_remaining' => $quantity,
            'unit_cost' => $unitCost,
            'received_date' => $receivedDate,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * Adjust the on-hand stock quantity for a product at a location.
     */
    public function stock(int $productId, int $warehouseId, int $locationId, int $delta): InventoryStock
    {
        $stock = InventoryStock::firstOrCreate(
            ['product_id' => $productId, 'location_id' => $locationId],
            ['warehouse_id' => $warehouseId, 'quantity' => 0, 'reserved_quantity' => 0],
        );

        $stock->update(['quantity' => $stock->quantity + $delta]);

        $this->syncProductQuantity($productId);

        return $stock->fresh();
    }

    /**
     * Keep Product.quantity_on_hand as the aggregate across all locations.
     */
    public function syncProductQuantity(int $productId): void
    {
        $total = (int) InventoryStock::where('product_id', $productId)->sum('quantity');

        Product::whereKey($productId)->update(['quantity_on_hand' => $total]);
    }
}
