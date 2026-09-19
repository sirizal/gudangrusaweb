<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialStatementLine;
use App\Models\JournalEntry;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorAddress;
use App\Services\Purchasing\GoodsReceiptService;
use App\Services\Purchasing\PurchaseOrderService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->term = PaymentTerm::factory()->create(['due_days' => 30]);
    $this->vendor = Vendor::factory()->create(['payment_term_id' => $this->term->id]);
    $this->vendor->addresses()->create(VendorAddress::factory()->make()->toArray());
    $this->product = Product::factory()->create(['price' => 100000]);
    $this->expense = Account::where('is_postable', true)->where('account_type', 'expense')->orderBy('account_code')->firstOrFail();
    $this->asset = Account::where('is_postable', true)->where('account_type', 'asset')->where('is_group', false)->orderBy('account_code')->firstOrFail();
    $this->grni = FinancialStatementLine::where('code', 'BS-GRNI')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
});

function receivableOrder($opts): object
{
    $order = app(PurchaseOrderService::class)->create([
        'company_id' => $opts['company']->id,
        'vendor_id' => $opts['vendor']->id,
        'po_date' => now()->toDateString(),
        'lines' => $opts['lines'],
    ]);

    app(PurchaseOrderService::class)->confirm($order);

    return $order->fresh();
}

it('posts a goods receipt journal for general and capex lines (Dr expense/asset, Cr GRNI)', function () {
    $order = receivableOrder([
        'company' => $this->company,
        'vendor' => $this->vendor,
        'lines' => [
            ['purchase_type' => 'general', 'account_id' => $this->expense->id, 'quantity' => 2, 'unit_price' => 100000],
            ['purchase_type' => 'capex', 'account_id' => $this->asset->id, 'quantity' => 1, 'unit_price' => 500000],
        ],
    ]);

    $receipt = app(GoodsReceiptService::class)->receive($order, [
        'receipt_date' => now()->toDateString(),
        'lines' => $order->lines->map(fn ($line): array => [
            'purchase_order_line_id' => $line->id,
            'quantity_received' => $line->quantity,
        ])->all(),
    ]);

    $journal = JournalEntry::findOrFail($receipt->journal_entry_id);

    expect((float) $journal->lines->sum('debit'))->toBe((float) $journal->lines->sum('credit'))
        ->and((float) $journal->lines->sum('debit'))->toBe(700000.0)
        ->and((float) $journal->lines->where('account_id', $this->grni->id)->first()->credit)->toBe(700000.0)
        ->and((float) $journal->lines->where('account_id', $this->expense->id)->first()->debit)->toBe(200000.0)
        ->and((float) $journal->lines->where('account_id', $this->asset->id)->first()->debit)->toBe(500000.0);

    expect($receipt->journal_entry_id)->not->toBeNull();
});

it('refuses to receive inventory lines on the purchasing panel', function () {
    $order = receivableOrder([
        'company' => $this->company,
        'vendor' => $this->vendor,
        'lines' => [
            ['purchase_type' => 'inventory', 'product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => 100000],
        ],
    ]);

    expect(fn () => app(GoodsReceiptService::class)->receive($order, [
        'receipt_date' => now()->toDateString(),
        'lines' => [
            ['purchase_order_line_id' => $order->lines->first()->id, 'quantity_received' => 3],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'received by the warehouse');
});

it('refuses to over-receive a line', function () {
    $order = receivableOrder([
        'company' => $this->company,
        'vendor' => $this->vendor,
        'lines' => [
            ['purchase_type' => 'general', 'account_id' => $this->expense->id, 'quantity' => 2, 'unit_price' => 100000],
        ],
    ]);

    expect(fn () => app(GoodsReceiptService::class)->receive($order, [
        'receipt_date' => now()->toDateString(),
        'lines' => [
            ['purchase_order_line_id' => $order->lines->first()->id, 'quantity_received' => 5],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'exceeds the remaining quantity');
});
