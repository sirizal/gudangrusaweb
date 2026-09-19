<?php

use App\Enums\WarehouseLocationType;
use App\Models\Company;
use App\Models\InventoryLayer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\Purchasing\PurchaseOrderService;
use App\Services\Wms\InboundService;
use App\Services\Wms\StockTransferService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);
    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
    $this->from = WarehouseLocation::factory()->create(['warehouse_id' => $this->warehouse->id, 'type' => WarehouseLocationType::Storage]);
    $this->to = WarehouseLocation::factory()->create(['warehouse_id' => $this->warehouse->id, 'type' => WarehouseLocationType::Picking]);
    $this->product = Product::factory()->create(['price' => 100000]);

    $vendor = Vendor::factory()->create();
    $po = app(PurchaseOrderService::class)->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'po_date' => now()->toDateString(),
        'lines' => [['purchase_type' => 'inventory', 'product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 100000]],
    ]);
    app(PurchaseOrderService::class)->confirm($po);
    app(InboundService::class)->receive($po->fresh(), [
        'warehouse_id' => $this->warehouse->id,
        'location_id' => $this->from->id,
        'receipt_date' => now()->toDateString(),
        'lines' => [['purchase_order_line_id' => $po->lines->first()->id, 'quantity_received' => 10]],
    ]);
});

it('transfers stock between locations preserving FIFO cost', function () {
    app(StockTransferService::class)->transfer([
        'warehouse_id' => $this->warehouse->id,
        'from_location_id' => $this->from->id,
        'to_location_id' => $this->to->id,
        'transfer_date' => now()->toDateString(),
        'lines' => [['product_id' => $this->product->id, 'quantity' => 4]],
    ]);

    $fromStock = InventoryStock::where('product_id', $this->product->id)->where('location_id', $this->from->id)->firstOrFail();
    $toStock = InventoryStock::where('product_id', $this->product->id)->where('location_id', $this->to->id)->firstOrFail();

    expect($fromStock->quantity)->toBe(6)
        ->and($toStock->quantity)->toBe(4);

    $toLayer = InventoryLayer::where('product_id', $this->product->id)->where('location_id', $this->to->id)->firstOrFail();
    expect($toLayer->quantity_remaining)->toBe(4)
        ->and((float) $toLayer->unit_cost)->toBe(100000.0);
});

it('rejects a transfer when locations belong to another warehouse', function () {
    $other = WarehouseLocation::factory()->create();

    expect(fn () => app(StockTransferService::class)->transfer([
        'warehouse_id' => $this->warehouse->id,
        'from_location_id' => $this->from->id,
        'to_location_id' => $other->id,
        'lines' => [['product_id' => $this->product->id, 'quantity' => 1]],
    ]))->toThrow(InvalidArgumentException::class, 'must belong to the selected warehouse');
});
