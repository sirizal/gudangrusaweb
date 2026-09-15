<?php

namespace App\Services\Accounting;

use App\Models\AccountingPeriod;
use App\Models\JournalEntry;

class JournalNumberGenerator
{
    /**
     * Generate the next journal number in the format JV-YYYYMM-####.
     */
    public function next(AccountingPeriod $period): string
    {
        $prefix = 'JV-'.$period->start_date->format('Y').str_pad((string) $period->period_number, 2, '0', STR_PAD_LEFT).'-';

        $last = JournalEntry::withTrashed()
            ->where('journal_number', 'like', $prefix.'%')
            ->orderByDesc('journal_number')
            ->value('journal_number');

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
