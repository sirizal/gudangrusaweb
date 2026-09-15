<?php

namespace App\Services\Accounting;

use App\Enums\JournalStatus;
use App\Enums\PeriodStatus;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PeriodService
{
    /**
     * Generate the 12 monthly accounting periods for a fiscal year.
     */
    public function generateForFiscalYear(FiscalYear $fiscalYear): void
    {
        DB::transaction(function () use ($fiscalYear): void {
            for ($month = 1; $month <= 12; $month++) {
                if ($fiscalYear->accountingPeriods()->where('period_number', $month)->exists()) {
                    continue;
                }

                $monthStart = $fiscalYear->start_date->copy()->addMonths($month - 1)->startOfMonth();

                AccountingPeriod::create([
                    'fiscal_year_id' => $fiscalYear->id,
                    'period_number' => $month,
                    'period_name' => $monthStart->translatedFormat('F Y'),
                    'start_date' => $monthStart,
                    'end_date' => $monthStart->copy()->endOfMonth(),
                    'status' => PeriodStatus::Open,
                ]);
            }
        });
    }

    /**
     * Close an accounting period. All journals within the period must be
     * posted (or reversed) before it can be locked.
     */
    public function close(AccountingPeriod $period, ?User $actor = null): void
    {
        if ($period->status !== PeriodStatus::Open) {
            throw new InvalidArgumentException('The period is already closed.');
        }

        $unposted = $period->journalEntries()
            ->whereNotIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
            ->count();

        if ($unposted > 0) {
            throw new InvalidArgumentException('Cannot close the period while it still contains unposted journals.');
        }

        $period->update(['status' => PeriodStatus::Closed]);
        $period->recordAudit('period_closed');
    }

    public function reopen(AccountingPeriod $period, ?User $actor = null): void
    {
        if ($period->status !== PeriodStatus::Closed) {
            throw new InvalidArgumentException('The period is already open.');
        }

        $period->update(['status' => PeriodStatus::Open]);
        $period->recordAudit('period_reopened');
    }
}
