<?php

namespace App\Services\Wms;

use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Models\OutboundShipment;
use App\Models\OutboundShipmentLine;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Accounting\JournalNumberGenerator;
use App\Services\Concerns\PostsJournals;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OutboundService
{
    use PostsJournals;

    public function __construct(
        private readonly WmsNumberGenerator $numbers,
        private readonly FifoService $fifo,
        private readonly JournalNumberGenerator $journalNumbers,
    ) {}

    /**
     * Ship inventory for a delivered sales order from a warehouse location.
     *
     * @param  array<string, mixed>  $data
     */
    public function ship(SalesOrder $order, array $data, ?User $actor = null): OutboundShipment
    {
        if ($order->status !== SalesOrderStatus::Delivered) {
            throw new InvalidArgumentException('Only delivered sales orders can be shipped from the warehouse.');
        }

        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        $locationId = (int) ($data['location_id'] ?? 0);

        if (! $warehouseId || ! $locationId) {
            throw new InvalidArgumentException('A warehouse and location are required.');
        }

        $shipmentDate = $data['shipment_date'] ?? now()->toDateString();
        $order->load('lines');

        $selected = [];

        foreach ($data['lines'] ?? [] as $line) {
            $quantity = (int) ($line['quantity_shipped'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            /** @var SalesOrderLine|null $soLine */
            $soLine = $order->lines->firstWhere('id', $line['sales_order_line_id'] ?? null);

            if (! $soLine) {
                throw new InvalidArgumentException('A selected line does not belong to this sales order.');
            }

            if (! $soLine->product_id) {
                throw new InvalidArgumentException('Only product lines can be shipped from the warehouse.');
            }

            $alreadyShipped = (int) OutboundShipmentLine::where('sales_order_line_id', $soLine->id)->sum('quantity_shipped');
            $remaining = $soLine->quantity - $alreadyShipped;

            if ($quantity > $remaining) {
                throw new InvalidArgumentException('Shipped quantity exceeds the remaining quantity of '.$remaining.'.');
            }

            $selected[] = [$soLine, $quantity];
        }

        if ($selected === []) {
            throw new InvalidArgumentException('There are no lines to ship.');
        }

        return DB::transaction(function () use ($order, $warehouseId, $locationId, $shipmentDate, $data, $selected, $actor): OutboundShipment {
            $shipment = OutboundShipment::create([
                'shipment_code' => $this->numbers->nextOutboundCode(Carbon::parse($shipmentDate)),
                'sales_order_id' => $order->id,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'shipment_date' => $shipmentDate,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
            ]);

            $totalCost = 0.0;

            foreach ($selected as [$soLine, $quantity]) {
                $result = $this->fifo->consume($soLine->product, $locationId, $quantity);
                $cost = $result['cost'];
                $totalCost += $cost;

                $this->fifo->stock($soLine->product_id, $warehouseId, $locationId, -$quantity);

                $shipment->lines()->create([
                    'sales_order_line_id' => $soLine->id,
                    'quantity_shipped' => $quantity,
                    'cost' => $cost,
                ]);

                StockMovement::create([
                    'product_id' => $soLine->product_id,
                    'warehouse_id' => $warehouseId,
                    'location_id' => $locationId,
                    'type' => StockMovementType::Outbound,
                    'quantity' => -$quantity,
                    'unit_cost' => $quantity > 0 ? round($cost / $quantity, 2) : 0,
                    'total_cost' => $cost,
                    'reference_type' => OutboundShipment::class,
                    'reference_id' => $shipment->id,
                    'movement_date' => $shipmentDate,
                ]);
            }

            $cogs = $this->resolveAccount('IS-COS', $order->company_id);
            $inventory = $this->resolveAccount('BS-INV', $order->company_id);

            $journal = $this->postJournal([
                ['account_id' => $cogs->id, 'description' => 'COGS '.$shipment->shipment_code, 'debit' => $totalCost, 'credit' => 0],
                ['account_id' => $inventory->id, 'description' => 'Inventory issued '.$shipment->shipment_code, 'debit' => 0, 'credit' => $totalCost],
            ], [
                'company_id' => $order->company_id,
                'date' => $shipmentDate,
                'description' => 'Warehouse outbound '.$shipment->shipment_code,
                'source' => 'wms_outbound',
                'reference_type' => OutboundShipment::class,
                'reference_id' => $shipment->id,
            ], $actor);

            $shipment->update(['total_cost' => $totalCost, 'journal_entry_id' => $journal->id]);

            $shipment->recordAudit('wms_outbound_posted', ['shipment_code' => $shipment->shipment_code, 'journal_number' => $journal->journal_number]);

            return $shipment->fresh();
        });
    }
}
