<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StatementType: string implements HasLabel
{
    case IncomeStatement = 'income_statement';

    case BalanceSheet = 'balance_sheet';

    case CashFlow = 'cash_flow';

    public function getLabel(): string
    {
        return match ($this) {
            self::IncomeStatement => 'Income Statement',
            self::BalanceSheet => 'Balance Sheet',
            self::CashFlow => 'Cash Flow',
        };
    }
}
