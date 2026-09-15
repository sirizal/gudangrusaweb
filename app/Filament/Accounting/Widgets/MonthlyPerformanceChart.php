<?php

namespace App\Filament\Accounting\Widgets;

use App\Services\Accounting\DashboardService;
use Filament\Widgets\ChartWidget;

class MonthlyPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'lg';

    protected ?string $heading = 'Monthly Revenue & Expense';

    protected function getData(): array
    {
        $series = app(DashboardService::class)->monthlyPerformance();
        $format = fn (float $value): string => 'Rp '.number_format($value, 0, ',', '.');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $series['revenue'],
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'Expenses',
                    'data' => $series['expense'],
                    'backgroundColor' => '#f43f5e',
                ],
                [
                    'label' => 'Profit',
                    'data' => $series['profit'],
                    'backgroundColor' => '#6366f1',
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
