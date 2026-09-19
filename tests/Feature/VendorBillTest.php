<?php

use App\Enums\VendorBillStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialStatementLine;
use App\Models\JournalEntry;
use App\Models\PaymentTerm;
use App\Models\Vendor;
use App\Models\VendorAddress;
use App\Services\Purchasing\GoodsReceiptService;
use App\Services\Purchasing\PurchaseOrderService;
use App\Services\Purchasing\VendorBillService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->term = PaymentTerm::factory()->create(['due_days' => 30]);
    $this->vendor = Vendor::factory()->create(['payment_term_id' => $this->term->id, 'is_pkp' => true]);
    $this->vendor->addresses()->create(VendorAddress::factory()->make()->toArray());
    $this->expense = Account::where('is_postable', true)->where('account_type', 'expense')->orderBy('account_code')->firstOrFail();

    $this->grni = FinancialStatementLine::where('code', 'BS-GRNI')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
    $this->ap = FinancialStatementLine::where('code', 'BS-AP')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
});

it('clears GRNI and books input VAT and the payable when a bill is posted', function () {
    $order = app(PurchaseOrderService::class)->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'po_date' => now()->toDateString(),
        'lines' => [
            ['purchase_type' => 'general', 'account_id' => $this->expense->id, 'quantity' => 1, 'unit_price' => 1000000],
        ],
    ]);
    app(PurchaseOrderService::class)->confirm($order);

    app(GoodsReceiptService::class)->receive($order->fresh(), [
        'receipt_date' => now()->toDateString(),
        'lines' => [['purchase_order_line_id' => $order->lines->first()->id, 'quantity_received' => 1]],
    ]);

    $bill = app(VendorBillService::class)->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'purchase_order_id' => $order->id,
        'bill_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'lines' => [
            ['purchase_type' => 'general', 'account_id' => $this->expense->id, 'quantity' => 1, 'unit_price' => 1000000],
        ],
    ]);

    expect($bill->status)->toBe(VendorBillStatus::Draft);

    app(VendorBillService::class)->post($bill);
    $bill->refresh();

    // net 1,000,000 + 11% PPN = 1,110,000
    expect($bill->status)->toBe(VendorBillStatus::Posted)
        ->and((float) $bill->tax_amount)->toBe(110000.0)
        ->and((float) $bill->total)->toBe(1110000.0);

    $journal = JournalEntry::findOrFail($bill->journal_entry_id);

    expect((float) $journal->lines->sum('debit'))->toBe((float) $journal->lines->sum('credit'))
        ->and((float) $journal->lines->where('account_id', $this->grni->id)->first()->debit)->toBe(1000000.0)
        ->and((float) $journal->lines->where('account_id', $this->ap->id)->first()->credit)->toBe(1110000.0)
        ->and($journal->lines)->toHaveCount(3);
});

it('posts a net-only bill for a non-PKP vendor', function () {
    $vendor = Vendor::factory()->create(['is_pkp' => false]);

    $bill = app(VendorBillService::class)->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'bill_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'lines' => [
            ['purchase_type' => 'general', 'account_id' => $this->expense->id, 'quantity' => 1, 'unit_price' => 500000],
        ],
    ]);

    app(VendorBillService::class)->post($bill);

    expect((float) $bill->fresh()->total)->toBe(500000.0)
        ->and((float) $bill->fresh()->tax_amount)->toBe(0.0);
});
