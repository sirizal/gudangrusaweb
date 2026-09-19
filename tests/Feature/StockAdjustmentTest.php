<?php

use App\Enums\WarehouseLocationType;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialStatementLine;
use App\Models\InventoryLayer;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\Purchasing\PurchaseOrderService;
use App\Services\Wms\InboundService;
use App\Services\Wms\StockAdjustmentService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);
    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
    $this->location = WarehouseLocation::factory()->create(['warehouse_id' => $this->warehouse->id, 'type' => WarehouseLocationType::Storage]);
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
        'location_id' => $this->location->id,
        'receipt_date' => now()->toDateString(),
        'lines' => [['purchase_order_line_id' => $po->lines->first()->id, 'quantity_received' => 10]],
    ]);

    $this->inventory = FinancialStatementLine::where('code', 'BS-INV')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
    $this->expense = FinancialStatementLine::where('code', 'IS-OE')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
});

it('adjusts stock down and posts a loss journal', function () {
    $adjustment = app(StockAdjustmentService::class)->adjust([
        'warehouse_id' => $this->warehouse->id,
        'location_id' => $this->location->id,
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Shrinkage',
        'lines' => [['product_id' => $this->product->id, 'quantity_delta' => -3, 'unit_cost' => 100000]],
    ]);

    expect((float) $adjustment->total_cost)->toBe(-300000.0);

    $layer = InventoryLayer::where('product_id', $this->product->id)->firstOrFail();
    expect($layer->quantity_remaining)->toBe(7);

    $journal = JournalEntry::findOrFail($adjustment->journal_entry_id);

    expect((float) $journal->lines->where('account_id', $this->expense->id)->first()->debit)->toBe(300000.0)
        ->and((float) $journal->lines->where('account_id', $this->inventory->id)->first()->credit)->toBe(300000.0);
});

it('adjusts stock up and creates a FIFO layer', function () {
    app(StockAdjustmentService::class)->adjust([
        'warehouse_id' => $this->warehouse->id,
        'location_id' => $this->location->id,
        'adjustment_date' => now()->toDateString(),
        'lines' => [['product_id' => $this->product->id, 'quantity_delta' => 2, 'unit_cost' => 100000]],
    ]);

    $total = (int) InventoryLayer::where('product_id', $this->product->id)->sum('quantity_remaining');
    expect($total)->toBe(12);
});
