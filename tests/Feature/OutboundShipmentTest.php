<?php

use App\Enums\WarehouseLocationType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FinancialStatementLine;
use App\Models\InventoryLayer;
use App\Models\InventoryStock;
use App\Models\JournalEntry;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\Purchasing\PurchaseOrderService;
use App\Services\Sales\SalesOrderService;
use App\Services\Wms\InboundService;
use App\Services\Wms\OutboundService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
    $this->storage = WarehouseLocation::factory()->create(['warehouse_id' => $this->warehouse->id, 'type' => WarehouseLocationType::Storage]);
    $this->product = Product::factory()->create(['price' => 250000]);

    // Stock the warehouse with 10 units at cost 100000 via a PO inbound.
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
        'location_id' => $this->storage->id,
        'receipt_date' => now()->toDateString(),
        'lines' => [['purchase_order_line_id' => $po->lines->first()->id, 'quantity_received' => 10]],
    ]);

    // A delivered sales order for 4 units.
    $term = PaymentTerm::factory()->create(['due_days' => 30]);
    $customer = Customer::factory()->create(['payment_term_id' => $term->id, 'is_pkp' => false]);
    $customer->addresses()->create(CustomerAddress::factory()->make()->toArray());

    $this->salesOrder = app(SalesOrderService::class)->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'order_date' => now()->toDateString(),
        'lines' => [['product_id' => $this->product->id, 'quantity' => 4, 'unit_price' => 250000]],
    ]);

    foreach (range(1, 5) as $_) {
        $this->salesOrder = app(SalesOrderService::class)->advance($this->salesOrder->fresh());
    }

    $this->cogs = FinancialStatementLine::where('code', 'IS-COS')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
    $this->inventory = FinancialStatementLine::where('code', 'BS-INV')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
});

it('ships FIFO stock for a delivered sales order and posts Dr COGS / Cr Inventory', function () {
    $line = $this->salesOrder->lines->first();

    $shipment = app(OutboundService::class)->ship($this->salesOrder->fresh(), [
        'warehouse_id' => $this->warehouse->id,
        'location_id' => $this->storage->id,
        'shipment_date' => now()->toDateString(),
        'lines' => [
            ['sales_order_line_id' => $line->id, 'quantity_shipped' => 4],
        ],
    ]);

    expect($shipment->shipment_code)->toMatch('/^WSO-\d{4}-\d{4}$/')
        ->and((float) $shipment->total_cost)->toBe(400000.0);

    $layer = InventoryLayer::where('product_id', $this->product->id)->firstOrFail();
    expect($layer->quantity_remaining)->toBe(6);

    expect(InventoryStock::where('product_id', $this->product->id)->where('location_id', $this->storage->id)->firstOrFail()->quantity)->toBe(6);

    $journal = JournalEntry::findOrFail($shipment->journal_entry_id);

    expect((float) $journal->lines->sum('debit'))->toBe((float) $journal->lines->sum('credit'))
        ->and((float) $journal->lines->where('account_id', $this->cogs->id)->first()->debit)->toBe(400000.0)
        ->and((float) $journal->lines->where('account_id', $this->inventory->id)->first()->credit)->toBe(400000.0);
});

it('rejects shipping more than the available stock', function () {
    $line = $this->salesOrder->lines->first();

    expect(fn () => app(OutboundService::class)->ship($this->salesOrder->fresh(), [
        'warehouse_id' => $this->warehouse->id,
        'location_id' => $this->storage->id,
        'lines' => [['sales_order_line_id' => $line->id, 'quantity_shipped' => 4]],
    ]))->not->toThrow(InvalidArgumentException::class);

    // Now stock is 6; request 70 via a second shipment on the same line should fail remaining-qty check first.
    expect(fn () => app(OutboundService::class)->ship($this->salesOrder->fresh(), [
        'warehouse_id' => $this->warehouse->id,
        'location_id' => $this->storage->id,
        'lines' => [['sales_order_line_id' => $line->id, 'quantity_shipped' => 1]],
    ]))->toThrow(InvalidArgumentException::class, 'exceeds the remaining quantity');
});
