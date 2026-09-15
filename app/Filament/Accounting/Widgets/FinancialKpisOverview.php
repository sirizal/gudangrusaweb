<?php

namespace App\Filament\Accounting\Widgets;

use App\Services\Accounting\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialKpisOverview extends BaseWidget
{
    /**
     * Spans the full width so the stat cards sit beside one another.
     */
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $kpis = app(DashboardService::class)->kpis();

        if (! $kpis['fiscal_year']) {
            return [
                Stat::make('Revenue', 0)->description('No fiscal year configured'),
            ];
        }

        $format = fn (float $value): string => 'Rp '.number_format($value, 0, ',', '.');
        $formatPercent = fn (float $value): string => number_format($value, 0, ',', '.').'%';

        $utilizationDescription = $kpis['budget_utilization'] === null
            ? 'No active budget'
            : $format((float) $kpis['budget_utilization']).'% used';

        return [
            Stat::make('Revenue', $format($kpis['revenue']))->color('success'),
            Stat::make('Gross Profit', $format($kpis['gross_profit']))->color('success'),
            Stat::make('Operating Profit', $format($kpis['operating_profit']))->color('success'),
            Stat::make('Net Profit', $format($kpis['net_profit']))->color('success'),
            Stat::make('Cash Balance', $format($kpis['cash_balance']))->color('success'),
            Stat::make('Accounts Receivable', $format($kpis['accounts_receivable']))->color('info'),
            Stat::make('Accounts Payable', $format($kpis['accounts_payable']))->color('info'),
            Stat::make('Budget Utilization', $kpis['budget_utilization'] === null ? '—' : $formatPercent((float) $kpis['budget_utilization']))
                ->description($utilizationDescription)
                ->color('warning'),
            Stat::make('YTD Revenue', $format($kpis['ytd_revenue']))->color('success'),
            Stat::make('YTD Expense', $format($kpis['ytd_expense']))->color('danger'),
        ];
    }
}
