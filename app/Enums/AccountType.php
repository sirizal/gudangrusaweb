<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AccountType: string implements HasLabel
{
    case Asset = 'asset';

    case Liability = 'liability';

    case Equity = 'equity';

    case Revenue = 'revenue';

    case CostOfSales = 'cost_of_sales';

    case Expense = 'expense';

    case OtherIncome = 'other_income';

    case OtherExpense = 'other_expense';

    public function getLabel(): string
    {
        return match ($this) {
            self::Asset => 'Asset / Aset',
            self::Liability => 'Liability / Liabilitas',
            self::Equity => 'Equity / Ekuitas',
            self::Revenue => 'Revenue / Pendapatan',
            self::CostOfSales => 'Cost of Sales / HPP',
            self::Expense => 'Expense / Beban',
            self::OtherIncome => 'Other Income',
            self::OtherExpense => 'Other Expense',
        };
    }

    /**
     * The default normal balance for this account type.
     */
    public function defaultNormalBalance(): NormalBalance
    {
        return match ($this) {
            self::Asset, self::CostOfSales, self::Expense, self::OtherExpense => NormalBalance::Debit,
            self::Liability, self::Equity, self::Revenue, self::OtherIncome => NormalBalance::Credit,
        };
    }
}
