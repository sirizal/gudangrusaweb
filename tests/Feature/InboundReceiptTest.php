<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Enums\WarehouseLocationType;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialStatementLine;
use App\Models\InventoryLayer;
use App\Models\InventoryStock;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\Purchasing\PurchaseOrderService;
use App\Services\Wms\InboundService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
    $this->receiving = WarehouseLocation::factory()->create(['warehouse_id' => $this->warehouse->id, 'type' => WarehouseLocationType::Receiving]);
    $this->vendor = Vendor::factory()->create();
    $this->product = Product::factory()->create(['price' => 100000]);

    $this->order = app(PurchaseOrderService::class)->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'po_date' => now()->toDateString(),
        'lines' => [
            ['purchase_type' => 'inventory', 'product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 100000],
        ],
    ]);
    app(PurchaseOrderService::class)->confirm($this->order);

    $this->inventory = FinancialStatementLine::where('code', 'BS-INV')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
    $this->grni = FinancialStatementLine::where('code', 'BS-GRNI')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
});

it('receives inventory from a purchase order, creates a FIFO layer, and posts Dr Inventory / Cr GRNI', function () {
    $line = $this->order->lines->first();

    $receipt = app(InboundService::class)->receive($this->order->fresh(), [
        'warehouse_id' => $this->warehouse->id,
        'location_id' => $this->receiving->id,
        'receipt_date' => now()->toDateString(),
        'lines' => [
            ['purchase_order_line_id' => $line->id, 'quantity_received' => 10],
        ],
    ]);

    expect($receipt->receipt_code)->toMatch('/^WGR-\d{4}-\d{4}$/')
        ->and((float) $receipt->total)->toBe(1000000.0)
        ->and($receipt->journal_entry_id)->not->toBeNull();

    $layer = InventoryLayer::where('product_id', $this->product->id)->firstOrFail();

    expect($layer->quantity_received)->toBe(10)
        ->and($layer->quantity_remaining)->toBe(10)
        ->and((float) $layer->unit_cost)->toBe(100000.0);

    expect(InventoryStock::where('product_id', $this->product->id)->firstOrFail()->quantity)->toBe(10);

    $movement = StockMovement::where('reference_id', $receipt->id)->firstOrFail();
    expect($movement->type)->toBe(StockMovementType::Inbound);

    $journal = JournalEntry::findOrFail($receipt->journal_entry_id);

    expect((float) $journal->lines->sum('debit'))->toBe((float) $journal->lines->sum('credit'))
        ->and((float) $journal->lines->where('account_id', $this->inventory->id)->first()->debit)->toBe(1000000.0)
        ->and((float) $journal->lines->where('account_id', $this->grni->id)->first()->credit)->toBe(1000000.0);

    expect($line->fresh()->received_quantity)->toBe(10)
        ->and($this->order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
});

it('rejects receiving a general/capex line on the warehouse', function () {
    $account = Account::where('is_postable', true)->where('account_type', 'expense')->firstOrFail();

    $order = app(PurchaseOrderService::class)->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'po_date' => now()->toDateString(),
        'lines' => [['purchase_type' => 'general', 'account_id' => $account->id, 'quantity' => 1, 'unit_price' => 50000]],
    ]);
    app(PurchaseOrderService::class)->confirm($order);

    expect(fn () => app(InboundService::class)->receive($order->fresh(), [
        'warehouse_id' => $this->warehouse->id,
        'location_id' => $this->receiving->id,
        'lines' => [['purchase_order_line_id' => $order->lines->first()->id, 'quantity_received' => 1]],
    ]))->toThrow(InvalidArgumentException::class, 'Only inventory lines');
});
