<?php

namespace App\Filament\Purchasing\Resources\PurchaseOrders\Pages;

use App\Filament\Purchasing\Resources\PurchaseOrders\Concerns\HandlesPurchaseOrderWorkflow;
use App\Filament\Purchasing\Resources\PurchaseOrders\Concerns\HydratesPurchaseOrderLines;
use App\Filament\Purchasing\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Services\Purchasing\PurchaseOrderService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseOrder extends EditRecord
{
    use HandlesPurchaseOrderWorkflow, HydratesPurchaseOrderLines;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [...$this->getPurchaseOrderWorkflowActions()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(PurchaseOrderService::class)->update($record, $data, auth()->user());
    }
}
