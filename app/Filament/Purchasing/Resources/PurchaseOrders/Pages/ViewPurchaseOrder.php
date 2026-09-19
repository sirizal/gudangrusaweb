<?php

namespace App\Filament\Purchasing\Resources\PurchaseOrders\Pages;

use App\Filament\Purchasing\Resources\PurchaseOrders\Concerns\HandlesPurchaseOrderWorkflow;
use App\Filament\Purchasing\Resources\PurchaseOrders\Concerns\HydratesPurchaseOrderLines;
use App\Filament\Purchasing\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    use HandlesPurchaseOrderWorkflow, HydratesPurchaseOrderLines;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->url(fn (): string => EditPurchaseOrder::getUrl(['record' => $this->record]))
                ->visible(fn (): bool => $this->record->status->isEditable()),
            ...$this->getPurchaseOrderWorkflowActions(),
        ];
    }
}
