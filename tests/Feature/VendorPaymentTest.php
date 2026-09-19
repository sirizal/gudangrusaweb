<?php

use App\Enums\VendorBillStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialStatementLine;
use App\Models\JournalEntry;
use App\Models\PaymentTerm;
use App\Models\Vendor;
use App\Models\VendorAddress;
use App\Models\VendorBill;
use App\Services\Purchasing\VendorBillService;
use App\Services\Purchasing\VendorPaymentService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->term = PaymentTerm::factory()->create(['due_days' => 30]);
    $this->vendor = Vendor::factory()->create(['payment_term_id' => $this->term->id, 'is_pkp' => false]);
    $this->vendor->addresses()->create(VendorAddress::factory()->make()->toArray());
    $this->expense = Account::where('is_postable', true)->where('account_type', 'expense')->orderBy('account_code')->firstOrFail();

    $this->ap = FinancialStatementLine::where('code', 'BS-AP')->firstOrFail()->accounts->first(fn (Account $a): bool => $a->is_postable && $a->is_active);
    // BS-CASH maps to group account 1110; the service resolves the first postable descendant (1111).
    $this->cash = Account::where('company_id', $this->company->id)
        ->where('account_code', '1111')
        ->firstOrFail();
});

function postedBill($opts): VendorBill
{
    $bill = app(VendorBillService::class)->create([
        'company_id' => $opts['company']->id,
        'vendor_id' => $opts['vendor']->id,
        'bill_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'lines' => [
            ['purchase_type' => 'general', 'account_id' => $opts['account']->id, 'quantity' => 1, 'unit_price' => $opts['amount']],
        ],
    ]);

    app(VendorBillService::class)->post($bill);

    return $bill->fresh();
}

it('records a partial payment and posts the AP/cash journal', function () {
    $bill = postedBill(['company' => $this->company, 'vendor' => $this->vendor, 'account' => $this->expense, 'amount' => 1000000]);

    $payment = app(VendorPaymentService::class)->pay($this->vendor, [
        'company_id' => $this->company->id,
        'payment_date' => now()->toDateString(),
        'reference' => 'PAY-001',
        'lines' => [['vendor_bill_id' => $bill->id, 'amount' => 400000]],
    ]);

    expect((float) $payment->total)->toBe(400000.0)
        ->and($bill->fresh()->status)->toBe(VendorBillStatus::PartiallyPaid)
        ->and((float) $bill->fresh()->paid_amount)->toBe(400000.0);

    $journal = JournalEntry::findOrFail($payment->journal_entry_id);

    expect((float) $journal->lines->sum('debit'))->toBe((float) $journal->lines->sum('credit'))
        ->and((float) $journal->lines->where('account_id', $this->ap->id)->first()->debit)->toBe(400000.0)
        ->and((float) $journal->lines->where('account_id', $this->cash->id)->first()->credit)->toBe(400000.0);
});

it('marks a bill paid after the full amount is settled', function () {
    $bill = postedBill(['company' => $this->company, 'vendor' => $this->vendor, 'account' => $this->expense, 'amount' => 500000]);

    app(VendorPaymentService::class)->pay($this->vendor, [
        'company_id' => $this->company->id,
        'payment_date' => now()->toDateString(),
        'lines' => [['vendor_bill_id' => $bill->id, 'amount' => 500000]],
    ]);

    expect($bill->fresh()->status)->toBe(VendorBillStatus::Paid)
        ->and($bill->fresh()->isFullyPaid())->toBeTrue();
});

it('rejects an overpayment', function () {
    $bill = postedBill(['company' => $this->company, 'vendor' => $this->vendor, 'account' => $this->expense, 'amount' => 500000]);

    expect(fn () => app(VendorPaymentService::class)->pay($this->vendor, [
        'company_id' => $this->company->id,
        'payment_date' => now()->toDateString(),
        'lines' => [['vendor_bill_id' => $bill->id, 'amount' => 600000]],
    ]))->toThrow(InvalidArgumentException::class, 'exceeds its remaining balance');
});
