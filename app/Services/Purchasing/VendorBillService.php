<?php

namespace App\Services\Purchasing;

use App\Enums\VendorBillStatus;
use App\Models\Account;
use App\Models\TaxCode;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\Accounting\JournalNumberGenerator;
use App\Services\Purchasing\Concerns\PostsPurchasingJournals;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VendorBillService
{
    use PostsPurchasingJournals;

    public function __construct(
        private readonly PurchasingNumberGenerator $numbers,
        private readonly JournalNumberGenerator $journalNumbers,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): VendorBill
    {
        $lines = $this->normalizeLines($data['lines'] ?? []);

        if ($lines === []) {
            throw new InvalidArgumentException('A vendor bill must have at least one line.');
        }

        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
        $billDate = $data['bill_date'] ?? now()->toDateString();

        return DB::transaction(function () use ($data, $lines, $subtotal, $billDate): VendorBill {
            $bill = VendorBill::create([
                'bill_code' => $this->numbers->nextBillCode(Carbon::parse($billDate)),
                'company_id' => $data['company_id'],
                'vendor_id' => $data['vendor_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'bill_date' => $billDate,
                'due_date' => $data['due_date'] ?? Carbon::parse($billDate)->addDays(30)->toDateString(),
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'status' => VendorBillStatus::Draft,
                'subtotal' => $subtotal,
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'tax_amount' => 0,
                'total' => $subtotal,
                'paid_amount' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $bill->lines()->create($line);
            }

            $bill->recordAudit('vendor_bill_created', ['bill_code' => $bill->bill_code]);

            return $bill->load('lines');
        });
    }

    public function update(VendorBill $bill, array $data, ?User $actor = null): VendorBill
    {
        if ($bill->status !== VendorBillStatus::Draft) {
            throw new InvalidArgumentException('Only draft vendor bills can be edited.');
        }

        $lines = $this->normalizeLines($data['lines'] ?? []);

        if ($lines === []) {
            throw new InvalidArgumentException('A vendor bill must have at least one line.');
        }

        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);

        return DB::transaction(function () use ($bill, $data, $lines, $subtotal): VendorBill {
            $bill->update([
                'vendor_id' => $data['vendor_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'bill_date' => $data['bill_date'] ?? $bill->bill_date->toDateString(),
                'due_date' => $data['due_date'] ?? $bill->due_date->toDateString(),
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            $bill->lines()->delete();

            foreach ($lines as $line) {
                $bill->lines()->create($line);
            }

            return $bill->fresh()->load('lines');
        });
    }

    /**
     * Post the bill: clear GRNI, book input VAT, and recognise the payable.
     */
    public function post(VendorBill $bill, ?User $actor = null): VendorBill
    {
        if ($bill->status !== VendorBillStatus::Draft) {
            throw new InvalidArgumentException('Only draft vendor bills can be posted.');
        }

        $net = round((float) $bill->subtotal - (float) $bill->discount_amount, 2);
        $tax = $this->computeInputTax($bill, $net);
        $total = round($net + $tax, 2);

        return DB::transaction(function () use ($bill, $net, $tax, $total, $actor): VendorBill {
            $grni = $this->resolveAccount('BS-GRNI', $bill->company_id);
            $ap = $this->resolveAccount('BS-AP', $bill->company_id);

            $lines = [[
                'account_id' => $grni->id,
                'description' => 'GRNI cleared '.$bill->bill_code,
                'debit' => $net,
                'credit' => 0,
            ]];

            if ($tax > 0) {
                $inputTax = $this->resolveInputTaxAccount($bill);

                if ($inputTax) {
                    $lines[] = [
                        'account_id' => $inputTax->id,
                        'description' => 'PPN masukan '.$bill->bill_code,
                        'debit' => $tax,
                        'credit' => 0,
                    ];
                }
            }

            $lines[] = [
                'account_id' => $ap->id,
                'description' => 'Accounts payable '.$bill->bill_code,
                'debit' => 0,
                'credit' => $total,
            ];

            $journal = $this->postJournal($lines, [
                'company_id' => $bill->company_id,
                'date' => $bill->bill_date,
                'description' => 'Vendor bill '.$bill->bill_code,
                'source' => 'purchasing_bill',
                'reference_type' => VendorBill::class,
                'reference_id' => $bill->id,
            ], $actor);

            $bill->update([
                'status' => VendorBillStatus::Posted,
                'tax_amount' => $tax,
                'total' => $total,
                'journal_entry_id' => $journal->id,
            ]);

            $bill->recordAudit('vendor_bill_posted', ['bill_code' => $bill->bill_code, 'journal_number' => $journal->journal_number]);

            return $bill->fresh();
        });
    }

    protected function computeInputTax(VendorBill $bill, float $net): float
    {
        if (! $bill->vendor->is_pkp || $net <= 0) {
            return 0.0;
        }

        $ppn = TaxCode::query()
            ->where('tax_type', 'PPN')
            ->where('rate', '>', 0)
            ->where(fn ($query) => $query->where('code', 'PPN-IN')->orWhere('code', 'like', 'PPN%'))
            ->orderByRaw("case when code = 'PPN-IN' then 0 else 1 end")
            ->first();

        return $ppn ? round($net * ((float) $ppn->rate / 100), 2) : 0.0;
    }

    protected function resolveInputTaxAccount(VendorBill $bill): ?Account
    {
        $ppn = TaxCode::query()
            ->where('tax_type', 'PPN')
            ->where('rate', '>', 0)
            ->where(fn ($query) => $query->where('code', 'PPN-IN')->orWhere('code', 'like', 'PPN%'))
            ->orderByRaw("case when code = 'PPN-IN' then 0 else 1 end")
            ->first();

        $account = $ppn?->account_id ? Account::find($ppn->account_id) : null;

        return $account && $account->is_postable && $account->is_active ? $account : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeLines(array $lines): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $quantity = (int) ($line['quantity'] ?? 1);
            $unitPrice = (float) ($line['unit_price'] ?? 0);

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Line quantity must be greater than zero.');
            }

            $normalized[] = [
                'purchase_type' => $line['purchase_type'] ?? 'inventory',
                'product_id' => $line['product_id'] ?? null,
                'account_id' => $line['account_id'] ?? null,
                'description' => $line['description'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
            ];
        }

        return $normalized;
    }
}
