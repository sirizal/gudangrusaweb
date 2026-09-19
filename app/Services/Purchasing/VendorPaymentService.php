<?php

namespace App\Services\Purchasing;

use App\Enums\VendorBillStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\VendorPayment;
use App\Services\Accounting\JournalNumberGenerator;
use App\Services\Purchasing\Concerns\PostsPurchasingJournals;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VendorPaymentService
{
    use PostsPurchasingJournals;

    public function __construct(
        private readonly PurchasingNumberGenerator $numbers,
        private readonly JournalNumberGenerator $journalNumbers,
    ) {}

    /**
     * Pay one or more vendor bills (partial payments allowed).
     *
     * @param  array<string, mixed>  $data
     */
    public function pay(Vendor $vendor, array $data, ?User $actor = null): VendorPayment
    {
        $allocations = $data['lines'] ?? [];

        if ($allocations === []) {
            throw new InvalidArgumentException('A vendor payment must allocate to at least one bill.');
        }

        $paymentDate = $data['payment_date'] ?? now()->toDateString();
        $total = 0.0;

        foreach ($allocations as $allocation) {
            $amount = (float) ($allocation['amount'] ?? 0);

            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment allocation amounts must be greater than zero.');
            }

            $bill = VendorBill::query()
                ->where('id', $allocation['vendor_bill_id'] ?? null)
                ->where('vendor_id', $vendor->id)
                ->first();

            if (! $bill) {
                throw new InvalidArgumentException('A selected bill does not belong to this vendor.');
            }

            if ($bill->status === VendorBillStatus::Draft) {
                throw new InvalidArgumentException('Bill '.$bill->bill_code.' must be posted before it can be paid.');
            }

            if ($bill->status === VendorBillStatus::Cancelled) {
                throw new InvalidArgumentException('Bill '.$bill->bill_code.' is cancelled.');
            }

            $remaining = round((float) $bill->total - (float) $bill->paid_amount, 2);

            if ($amount > $remaining) {
                throw new InvalidArgumentException('Payment for '.$bill->bill_code.' exceeds its remaining balance of '.number_format($remaining, 2, ',', '.').'.');
            }

            $total += $amount;
        }

        $total = round($total, 2);

        return DB::transaction(function () use ($vendor, $data, $allocations, $paymentDate, $total, $actor): VendorPayment {
            $payment = VendorPayment::create([
                'payment_code' => $this->numbers->nextPaymentCode(Carbon::parse($paymentDate)),
                'company_id' => $data['company_id'],
                'vendor_id' => $vendor->id,
                'payment_date' => $paymentDate,
                'total' => $total,
                'reference' => $data['reference'] ?? null,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($allocations as $allocation) {
                $bill = VendorBill::findOrFail($allocation['vendor_bill_id']);
                $amount = round((float) $allocation['amount'], 2);

                $payment->lines()->create([
                    'vendor_bill_id' => $bill->id,
                    'amount' => $amount,
                ]);

                $newPaid = round((float) $bill->paid_amount + $amount, 2);
                $bill->update([
                    'paid_amount' => $newPaid,
                    'status' => $newPaid >= (float) $bill->total ? VendorBillStatus::Paid : VendorBillStatus::PartiallyPaid,
                ]);
            }

            $ap = $this->resolveAccount('BS-AP', $payment->company_id);
            $cash = $this->resolveAccount('BS-CASH', $payment->company_id);

            $journal = $this->postJournal([
                [
                    'account_id' => $ap->id,
                    'description' => 'Accounts payable '.$payment->payment_code,
                    'debit' => $total,
                    'credit' => 0,
                ],
                [
                    'account_id' => $cash->id,
                    'description' => 'Cash paid '.$payment->payment_code,
                    'debit' => 0,
                    'credit' => $total,
                ],
            ], [
                'company_id' => $payment->company_id,
                'date' => $paymentDate,
                'description' => 'Vendor payment '.$payment->payment_code,
                'source' => 'purchasing_payment',
                'reference_type' => VendorPayment::class,
                'reference_id' => $payment->id,
            ], $actor);

            $payment->update(['journal_entry_id' => $journal->id]);
            $payment->recordAudit('vendor_payment_posted', ['payment_code' => $payment->payment_code, 'journal_number' => $journal->journal_number]);

            return $payment->fresh();
        });
    }
}
