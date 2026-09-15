<?php

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Services\Sales\SalesInvoiceService;
use App\Services\Sales\SalesOrderService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->term = PaymentTerm::factory()->create(['due_days' => 30]);
    $this->customer = Customer::factory()->create(['payment_term_id' => $this->term->id, 'is_pkp' => false]);
    $this->customer->addresses()->create(CustomerAddress::factory()->make()->toArray());
    $this->product = Product::factory()->create(['price' => 100000]);
});

function deliveredSalesOrder($service, array $opts): object
{
    $order = $service->create([
        'company_id' => $opts['company']->id,
        'customer_id' => $opts['customer']->id,
        'order_date' => $opts['date'] ?? now()->toDateString(),
        'payment_term_id' => $opts['term']->id,
        'lines' => [
            ['product_id' => $opts['product']->id, 'quantity' => $opts['qty'] ?? 2, 'unit_price' => $opts['price'] ?? 100000],
        ],
    ]);

    foreach (range(1, 5) as $_) {
        $order = $service->advance($order->fresh());
    }

    return $order;
}

it('issues an invoice from a delivered order with the due date from the payment term', function () {
    $order = deliveredSalesOrder(app(SalesOrderService::class), [
        'company' => $this->company,
        'customer' => $this->customer,
        'term' => $this->term,
        'product' => $this->product,
        'qty' => 2,
        'price' => 100000,
    ]);

    $invoice = $order->invoice()->firstOrFail();

    expect($invoice->invoice_code)->toMatch('/^INV-'.now()->year.'-\d{4}$/')
        ->and((float) $invoice->subtotal)->toBe(200000.0)
        ->and((float) $invoice->total)->toBe(200000.0)
        ->and($invoice->invoice_date->toDateString())->toBe(now()->toDateString())
        ->and($invoice->due_date->toDateString())->toBe(now()->addDays(30)->toDateString())
        ->and($invoice->status)->toBe(InvoiceStatus::Issued)
        ->and($invoice->journal_entry_id)->not->toBeNull();
});

it('computes aging for an invoice', function () {
    $order = deliveredSalesOrder(app(SalesOrderService::class), [
        'company' => $this->company,
        'customer' => $this->customer,
        'term' => $this->term,
        'product' => $this->product,
    ]);

    $invoice = $order->invoice()->firstOrFail();
    $invoice->forceFill([
        'invoice_date' => now()->subDays(90)->toDateString(),
        'due_date' => now()->subDays(60)->toDateString(),
        'paid_amount' => 0,
    ])->save();
    $invoice->refresh();

    expect($invoice->age_days)->toBeGreaterThanOrEqual(90)
        ->and($invoice->overdue_days)->toBeGreaterThanOrEqual(60)
        ->and($invoice->aging_bucket)->toBe('31-60 days');
});

it('records a payment and updates the invoice status', function () {
    $order = deliveredSalesOrder(app(SalesOrderService::class), [
        'company' => $this->company,
        'customer' => $this->customer,
        'term' => $this->term,
        'product' => $this->product,
        'qty' => 2,
        'price' => 100000,
    ]);

    $invoice = $order->invoice()->firstOrFail();

    app(SalesInvoiceService::class)->recordPayment($invoice, 120000, ['reference' => 'PAY-001']);
    $invoice->refresh();

    expect((float) $invoice->paid_amount)->toBe(120000.0)
        ->and($invoice->status)->toBe(InvoiceStatus::PartiallyPaid);

    app(SalesInvoiceService::class)->recordPayment($invoice, 80000, ['reference' => 'PAY-002']);
    $invoice->refresh();

    expect((float) $invoice->paid_amount)->toBe(200000.0)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->isFullyPaid())->toBeTrue();
});

it('rejects an overpayment', function () {
    $order = deliveredSalesOrder(app(SalesOrderService::class), [
        'company' => $this->company,
        'customer' => $this->customer,
        'term' => $this->term,
        'product' => $this->product,
        'qty' => 1,
        'price' => 100000,
    ]);

    $invoice = $order->invoice()->firstOrFail();

    expect(fn () => app(SalesInvoiceService::class)->recordPayment($invoice, 200000))
        ->toThrow(InvalidArgumentException::class, 'exceeds the remaining balance');
});

it('cancels an unpaid invoice', function () {
    $order = deliveredSalesOrder(app(SalesOrderService::class), [
        'company' => $this->company,
        'customer' => $this->customer,
        'term' => $this->term,
        'product' => $this->product,
    ]);

    $invoice = $order->invoice()->firstOrFail();

    app(SalesInvoiceService::class)->cancel($invoice);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Cancelled);
});

it('cannot cancel a paid invoice', function () {
    $order = deliveredSalesOrder(app(SalesOrderService::class), [
        'company' => $this->company,
        'customer' => $this->customer,
        'term' => $this->term,
        'product' => $this->product,
        'qty' => 1,
        'price' => 100000,
    ]);

    $invoice = $order->invoice()->firstOrFail();
    app(SalesInvoiceService::class)->recordPayment($invoice, 100000);

    expect(fn () => app(SalesInvoiceService::class)->cancel($invoice->fresh()))
        ->toThrow(InvalidArgumentException::class, 'paid invoice cannot be cancelled');
});
