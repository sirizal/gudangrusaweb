<?php

use App\Enums\SalesOrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Invoice;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Services\Sales\SalesOrderService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->term = PaymentTerm::factory()->create(['code' => 'NET30', 'name' => 'Net 30', 'due_days' => 30]);
    $this->customer = Customer::factory()->create(['payment_term_id' => $this->term->id, 'is_pkp' => false]);
    $this->customer->addresses()->create(CustomerAddress::factory()->make()->toArray());
    $this->product = Product::factory()->create(['price' => 100000]);
});

it('creates an order with an auto-generated code, lines, and totals', function () {
    $order = app(SalesOrderService::class)->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'order_date' => now()->toDateString(),
        'payment_term_id' => $this->term->id,
        'lines' => [
            ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 100000],
            ['quantity' => 1, 'description' => 'Service fee', 'unit_price' => 50000],
        ],
    ]);

    expect($order->order_code)->toMatch('/^SO-'.now()->year.'-\d{4}$/')
        ->and($order->status)->toBe(SalesOrderStatus::Raised)
        ->and($order->lines)->toHaveCount(2)
        ->and((float) $order->subtotal)->toBe(250000.0)
        ->and((float) $order->total)->toBe(250000.0);
});

it('advances through the status workflow and issues an invoice on delivery', function () {
    $order = app(SalesOrderService::class)->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'order_date' => now()->toDateString(),
        'lines' => [
            ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100000],
        ],
    ]);

    foreach ([
        SalesOrderStatus::Open,
        SalesOrderStatus::OnPick,
        SalesOrderStatus::OnPack,
        SalesOrderStatus::OnDelivery,
        SalesOrderStatus::Delivered,
    ] as $expected) {
        $order = app(SalesOrderService::class)->advance($order->fresh());
        expect($order->status)->toBe($expected);
    }

    expect($order->delivered_at)->not->toBeNull();
    expect(Invoice::where('sales_order_id', $order->id)->exists())->toBeTrue();
});

it('cannot advance a delivered order', function () {
    $order = app(SalesOrderService::class)->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'order_date' => now()->toDateString(),
        'lines' => [
            ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100000],
        ],
    ]);

    foreach (range(1, 5) as $_) {
        $order = app(SalesOrderService::class)->advance($order->fresh());
    }

    expect(fn () => app(SalesOrderService::class)->advance($order->fresh()))
        ->toThrow(InvalidArgumentException::class, 'cannot advance');
});

it('cancels an order before it is delivered', function () {
    $order = app(SalesOrderService::class)->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'order_date' => now()->toDateString(),
        'lines' => [
            ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100000],
        ],
    ]);

    $order = app(SalesOrderService::class)->advance($order->fresh());
    $order = app(SalesOrderService::class)->cancel($order);

    expect($order->status)->toBe(SalesOrderStatus::Cancelled);
});
