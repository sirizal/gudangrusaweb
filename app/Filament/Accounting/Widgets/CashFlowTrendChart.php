<?php

namespace App\Filament\Accounting\Widgets;

use App\Services\Accounting\DashboardService;
use Filament\Widgets\ChartWidget;

class CashFlowTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Cash Balance Trend';

    protected function getData(): array
    {
        $series = app(DashboardService::class)->cashFlowTrend();

        return [
            'datasets' => [
                [
                    'label' => 'Cash balance',
                    'data' => $series['cash'],
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
