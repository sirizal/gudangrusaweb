<?php

namespace App\Filament\Sales\Resources\SalesOrders\Pages;

use App\Filament\Sales\Resources\SalesOrders\Concerns\HandlesSalesOrderWorkflow;
use App\Filament\Sales\Resources\SalesOrders\SalesOrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesOrder extends ViewRecord
{
    use HandlesSalesOrderWorkflow;

    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit order')
                ->url(fn (): string => EditSalesOrder::getUrl(['record' => $this->record]))
                ->visible(fn (): bool => $this->record->status->value === 'raised'),
            ...$this->getSalesOrderWorkflowActions(),
        ];
    }
}
