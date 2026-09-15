<?php

namespace App\Services\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\JournalStatus;
use App\Enums\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\FinancialStatementLine;
use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\SalesOrder;
use App\Models\TaxCode;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\JournalNumberGenerator;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesInvoiceService
{
    public function __construct(
        private readonly SalesNumberGenerator $numbers,
        private readonly JournalNumberGenerator $journalNumbers,
        private readonly AccountingService $accounting,
    ) {}

    /**
     * Issue an invoice for a delivered order and post the AR/Revenue/Tax journal.
     */
    public function issueFromOrder(SalesOrder $order, ?User $actor = null): Invoice
    {
        if ($order->invoice()->exists()) {
            throw new InvalidArgumentException('Order '.$order->order_code.' already has an invoice.');
        }

        $customer = $order->customer;
        $dueDays = $order->paymentTerm?->due_days ?? $customer->paymentTerm?->due_days ?? 30;
        $invoiceDate = $order->order_date;

        $subtotal = (float) $order->subtotal;
        $discount = (float) $order->discount_amount;
        $tax = $this->computeTax($customer, $subtotal - $discount);
        $total = round($subtotal - $discount + $tax, 2);

        $period = $this->resolvePeriod($order->company_id, $invoiceDate);

        return DB::transaction(function () use ($order, $customer, $invoiceDate, $dueDays, $subtotal, $discount, $tax, $total, $period, $actor): Invoice {
            $invoice = Invoice::create([
                'invoice_code' => $this->numbers->nextInvoiceCode($invoiceDate),
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $invoiceDate->copy()->addDays($dueDays)->toDateString(),
                'payment_term_id' => $order->payment_term_id ?? $customer->payment_term_id,
                'status' => InvoiceStatus::Issued,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total' => $total,
                'paid_amount' => 0,
            ]);

            $ar = $this->resolveAccount('BS-AR', $order->company_id);
            $revenue = $this->resolveAccount('IS-REV', $order->company_id);
            $taxAccount = $tax > 0 ? $this->resolveAccount('BS-TAX', $order->company_id) : null;

            $journal = JournalEntry::create([
                'company_id' => $order->company_id,
                'journal_number' => $this->journalNumbers->next($period),
                'journal_date' => $invoiceDate->toDateString(),
                'accounting_period_id' => $period->id,
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'description' => 'Sales invoice '.$invoice->invoice_code,
                'status' => JournalStatus::Posted,
                'source' => 'sales_invoice',
                'posted_at' => now(),
                'posted_by' => $actor?->id ?? auth()->id(),
            ]);

            $journal->lines()->create([
                'account_id' => $ar->id,
                'description' => 'Accounts receivable '.$invoice->invoice_code,
                'debit' => $total,
                'credit' => 0,
            ]);
            $journal->lines()->create([
                'account_id' => $revenue->id,
                'description' => 'Sales revenue '.$invoice->invoice_code,
                'debit' => 0,
                'credit' => $subtotal - $discount,
            ]);
            if ($taxAccount) {
                $journal->lines()->create([
                    'account_id' => $taxAccount->id,
                    'description' => 'PPN keluaran '.$invoice->invoice_code,
                    'debit' => 0,
                    'credit' => $tax,
                ]);
            }

            $invoice->update(['journal_entry_id' => $journal->id]);
            $invoice->recordAudit('invoice_issued', ['invoice_code' => $invoice->invoice_code, 'journal_number' => $journal->journal_number]);

            return $invoice->fresh();
        });
    }

    /**
     * Record a customer payment, update the invoice status, and post the cash/AR journal.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordPayment(Invoice $invoice, float $amount, array $data = [], ?User $actor = null): Invoice
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            throw new InvalidArgumentException('A cancelled invoice cannot receive payments.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $remaining = round((float) $invoice->total - (float) $invoice->paid_amount, 2);

        if ($amount > $remaining) {
            throw new InvalidArgumentException('Payment amount exceeds the remaining balance of '.number_format($remaining, 2, ',', '.').'.');
        }

        $period = $this->resolvePeriod($invoice->salesOrder->company_id, $data['payment_date'] ?? now()->toDateString());

        return DB::transaction(function () use ($invoice, $amount, $data, $period, $actor): Invoice {
            $invoice->payments()->create([
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'reference' => $data['reference'] ?? null,
            ]);

            $newPaid = round((float) $invoice->paid_amount + $amount, 2);
            $status = $newPaid >= (float) $invoice->total ? InvoiceStatus::Paid : InvoiceStatus::PartiallyPaid;

            $invoice->update([
                'paid_amount' => $newPaid,
                'status' => $status,
            ]);

            $cash = $this->resolveAccount('BS-CASH', $invoice->salesOrder->company_id);
            $ar = $this->resolveAccount('BS-AR', $invoice->salesOrder->company_id);

            $journal = JournalEntry::create([
                'company_id' => $invoice->salesOrder->company_id,
                'journal_number' => $this->journalNumbers->next($period),
                'journal_date' => $data['payment_date'] ?? now()->toDateString(),
                'accounting_period_id' => $period->id,
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'description' => 'Payment for invoice '.$invoice->invoice_code,
                'status' => JournalStatus::Posted,
                'source' => 'sales_payment',
                'posted_at' => now(),
                'posted_by' => $actor?->id ?? auth()->id(),
            ]);

            $journal->lines()->create([
                'account_id' => $cash->id,
                'description' => 'Cash received '.$invoice->invoice_code,
                'debit' => $amount,
                'credit' => 0,
            ]);
            $journal->lines()->create([
                'account_id' => $ar->id,
                'description' => 'Accounts receivable '.$invoice->invoice_code,
                'debit' => 0,
                'credit' => $amount,
            ]);

            $invoice->recordAudit('invoice_payment', ['invoice_code' => $invoice->invoice_code, 'amount' => $amount, 'journal_number' => $journal->journal_number]);

            return $invoice->fresh();
        });
    }

    /**
     * Cancel an unpaid invoice and reverse its journal.
     */
    public function cancel(Invoice $invoice, ?User $actor = null): Invoice
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            throw new InvalidArgumentException('Invoice '.$invoice->invoice_code.' is already cancelled.');
        }

        if ($invoice->isFullyPaid()) {
            throw new InvalidArgumentException('A paid invoice cannot be cancelled.');
        }

        DB::transaction(function () use ($invoice, $actor): void {
            if ($invoice->journalEntry) {
                $this->accounting->reverse($invoice->journalEntry, $actor, [
                    'description' => 'Cancellation of invoice '.$invoice->invoice_code,
                ]);
            }

            $invoice->update(['status' => InvoiceStatus::Cancelled]);
            $invoice->recordAudit('invoice_cancelled', ['invoice_code' => $invoice->invoice_code]);
        });

        return $invoice->fresh();
    }

    protected function computeTax(Customer $customer, float $base): float
    {
        if (! $customer->is_pkp || $base <= 0) {
            return 0.0;
        }

        $ppn = TaxCode::query()
            ->where('tax_type', 'PPN')
            ->where('code', 'like', 'PPN%')
            ->where('rate', '>', 0)
            ->orderBy('id')
            ->first();

        $rate = $ppn?->rate ?? 0;

        return round($base * ($rate / 100), 2);
    }

    protected function resolveAccount(string $statementLineCode, int $companyId): Account
    {
        $line = FinancialStatementLine::query()
            ->where('code', $statementLineCode)
            ->with('accounts')
            ->first();

        foreach ($line?->accounts ?? [] as $account) {
            if ($account->is_postable && $account->is_active) {
                return $account;
            }

            if (! $account->is_group) {
                continue;
            }

            $descendant = $this->postableDescendant($account, $companyId);

            if ($descendant) {
                return $descendant;
            }
        }

        throw new InvalidArgumentException('No postable account is mapped to the '.$statementLineCode.' financial statement line.');
    }

    protected function postableDescendant(Account $group, int $companyId): ?Account
    {
        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->get();

        $byId = $accounts->keyBy('id');

        $descendants = $accounts
            ->filter(function (Account $account) use ($group, $byId): bool {
                if (! $account->is_postable || ! $account->is_active) {
                    return false;
                }

                $current = $account;

                while ($current?->parent_id) {
                    $current = $byId->get($current->parent_id);

                    if ($current?->id === $group->id) {
                        return true;
                    }
                }

                return false;
            })
            ->sortBy('account_code');

        return $descendants->first();
    }

    protected function resolvePeriod(int $companyId, string|CarbonInterface $date): AccountingPeriod
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        $fiscalYear = FiscalYear::query()
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();

        if (! $fiscalYear) {
            throw new InvalidArgumentException('No fiscal year covers the transaction date '.$date->toDateString().'.');
        }

        $period = AccountingPeriod::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();

        if (! $period) {
            throw new InvalidArgumentException('No accounting period covers the transaction date '.$date->toDateString().'.');
        }

        if ($period->status !== PeriodStatus::Open) {
            throw new InvalidArgumentException('Cannot post into the closed accounting period '.$period->period_name.'.');
        }

        return $period;
    }
}
