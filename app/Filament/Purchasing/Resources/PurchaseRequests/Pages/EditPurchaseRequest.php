<?php

namespace App\Filament\Purchasing\Resources\PurchaseRequests\Pages;

use App\Filament\Purchasing\Resources\PurchaseRequests\Concerns\HandlesPurchaseRequestWorkflow;
use App\Filament\Purchasing\Resources\PurchaseRequests\Concerns\HydratesPurchaseRequestLines;
use App\Filament\Purchasing\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Services\Purchasing\PurchaseRequestService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseRequest extends EditRecord
{
    use HandlesPurchaseRequestWorkflow, HydratesPurchaseRequestLines;

    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [...$this->getPurchaseRequestWorkflowActions()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(PurchaseRequestService::class)->update($record, $data, auth()->user());
    }
}
