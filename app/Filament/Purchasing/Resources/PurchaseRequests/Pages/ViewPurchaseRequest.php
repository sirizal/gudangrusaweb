<?php

namespace App\Filament\Purchasing\Resources\PurchaseRequests\Pages;

use App\Filament\Purchasing\Resources\PurchaseRequests\Concerns\HandlesPurchaseRequestWorkflow;
use App\Filament\Purchasing\Resources\PurchaseRequests\Concerns\HydratesPurchaseRequestLines;
use App\Filament\Purchasing\Resources\PurchaseRequests\PurchaseRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseRequest extends ViewRecord
{
    use HandlesPurchaseRequestWorkflow, HydratesPurchaseRequestLines;

    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->url(fn (): string => EditPurchaseRequest::getUrl(['record' => $this->record]))
                ->visible(fn (): bool => $this->record->status->isEditable()),
            ...$this->getPurchaseRequestWorkflowActions(),
        ];
    }
}
