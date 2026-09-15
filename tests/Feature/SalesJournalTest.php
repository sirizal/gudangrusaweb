<?php

use App\Enums\JournalStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FinancialStatementLine;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Services\Sales\SalesInvoiceService;
use App\Services\Sales\SalesOrderService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->term = PaymentTerm::factory()->create(['due_days' => 30]);
    $this->customer = Customer::factory()->create(['payment_term_id' => $this->term->id, 'is_pkp' => true]);
    $this->customer->addresses()->create(CustomerAddress::factory()->make()->toArray());
    $this->product = Product::factory()->create(['price' => 100000]);
});

function journalDeliveredSalesOrder($service, array $opts): object
{
    $order = $service->create([
        'company_id' => $opts['company']->id,
        'customer_id' => $opts['customer']->id,
        'order_date' => $opts['date'] ?? now()->toDateString(),
        'lines' => [
            ['product_id' => $opts['product']->id, 'quantity' => 2, 'unit_price' => 100000],
        ],
    ]);

    foreach (range(1, 5) as $_) {
        $order = $service->advance($order->fresh());
    }

    return $order;
}

function resolveStatementAccount(string $code): ?Account
{
    $line = FinancialStatementLine::where('code', $code)->first();

    foreach ($line?->accounts ?? [] as $account) {
        if ($account->is_postable && $account->is_active) {
            return $account;
        }

        if ($account->is_group) {
            $accounts = Account::where('company_id', $account->company_id)->get();
            $byId = $accounts->keyBy('id');

            $descendant = $accounts
                ->filter(function (Account $a) use ($account, $byId): bool {
                    if (! $a->is_postable || ! $a->is_active) {
                        return false;
                    }

                    $current = $a;

                    while ($current?->parent_id) {
                        $current = $byId->get($current->parent_id);

                        if ($current?->id === $account->id) {
                            return true;
                        }
                    }

                    return false;
                })
                ->sortBy('account_code')
                ->first();

            if ($descendant) {
                return $descendant;
            }
        }
    }

    return null;
}

it('posts a balanced AR/Revenue/Tax journal when the invoice is issued', function () {
    $order = journalDeliveredSalesOrder(app(SalesOrderService::class), [
        'company' => $this->company,
        'customer' => $this->customer,
        'product' => $this->product,
    ]);

    $invoice = $order->invoice()->firstOrFail();
    $journal = JournalEntry::findOrFail($invoice->journal_entry_id);

    // 2 x 100000 = 200000 subtotal; PKP -> 11% PPN = 22000; total 222000.
    expect($journal->status)->toBe(JournalStatus::Posted)
        ->and($journal->source)->toBe('sales_invoice')
        ->and($journal->reference_type)->toBe(Invoice::class)
        ->and($journal->reference_id)->toBe($invoice->id)
        ->and((float) $journal->lines->sum('debit'))->toBe((float) $journal->lines->sum('credit'))
        ->and($journal->lines->sum(fn ($line): float => (float) $line->debit))->toBe(222000.0);

    $ar = resolveStatementAccount('BS-AR');
    $revenue = resolveStatementAccount('IS-REV');
    $tax = resolveStatementAccount('BS-TAX');

    expect((float) $journal->lines->where('account_id', $ar->id)->first()->debit)->toBe(222000.0);
    expect((float) $journal->lines->where('account_id', $revenue->id)->first()->credit)->toBe(200000.0);
    expect((float) $journal->lines->where('account_id', $tax->id)->first()->credit)->toBe(22000.0);
});

it('posts a balanced Cash/AR journal when a payment is recorded', function () {
    $order = journalDeliveredSalesOrder(app(SalesOrderService::class), [
        'company' => $this->company,
        'customer' => $this->customer,
        'product' => $this->product,
    ]);

    $invoice = $order->invoice()->firstOrFail();
    app(SalesInvoiceService::class)->recordPayment($invoice, 100000, ['reference' => 'PAY-001']);

    $paymentJournal = JournalEntry::where('source', 'sales_payment')
        ->where('reference_id', $invoice->id)
        ->firstOrFail();

    $cash = resolveStatementAccount('BS-CASH');
    $ar = resolveStatementAccount('BS-AR');

    expect($paymentJournal->status)->toBe(JournalStatus::Posted)
        ->and((float) $paymentJournal->lines->where('account_id', $cash->id)->first()->debit)->toBe(100000.0)
        ->and((float) $paymentJournal->lines->where('account_id', $ar->id)->first()->credit)->toBe(100000.0)
        ->and((float) $paymentJournal->lines->sum('debit'))->toBe((float) $paymentJournal->lines->sum('credit'));
});

it('reverses the invoice journal when the invoice is cancelled', function () {
    $order = journalDeliveredSalesOrder(app(SalesOrderService::class), [
        'company' => $this->company,
        'customer' => $this->customer,
        'product' => $this->product,
    ]);

    $invoice = $order->invoice()->firstOrFail();
    $originalJournal = JournalEntry::findOrFail($invoice->journal_entry_id);

    app(SalesInvoiceService::class)->cancel($invoice);

    $originalJournal->refresh();

    expect($originalJournal->is_reversed)->toBeTrue();
    expect(JournalEntry::where('reversed_journal_id', $originalJournal->id)->exists())->toBeTrue();
});
