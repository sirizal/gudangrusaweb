<?php

namespace App\Filament\Accounting\Widgets;

use App\Services\Accounting\DashboardService;
use Filament\Widgets\ChartWidget;

class BudgetVsActualChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'lg';

    protected ?string $heading = 'Budget vs Actual';

    protected function getData(): array
    {
        $series = app(DashboardService::class)->budgetVsActualSeries();

        return [
            'datasets' => [
                [
                    'label' => 'Budget',
                    'data' => $series['budget'],
                    'backgroundColor' => '#a3e635',
                ],
                [
                    'label' => 'Actual',
                    'data' => $series['actual'],
                    'backgroundColor' => '#f59e0b',
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
