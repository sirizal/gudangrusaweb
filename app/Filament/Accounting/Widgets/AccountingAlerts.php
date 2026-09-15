<?php

namespace App\Filament\Accounting\Widgets;

use App\Services\Accounting\DashboardService;
use Filament\Widgets\Widget;

class AccountingAlerts extends Widget
{
    protected string $view = 'filament.widgets.accounting-alerts';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAlerts(): array
    {
        return app(DashboardService::class)->alerts();
    }
}
