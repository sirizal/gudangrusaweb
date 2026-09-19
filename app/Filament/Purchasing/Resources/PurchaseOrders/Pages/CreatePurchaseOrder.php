<?php

namespace App\Filament\Purchasing\Resources\PurchaseOrders\Pages;

use App\Filament\Purchasing\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Services\Purchasing\PurchaseOrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PurchaseOrderService::class)->create($data, auth()->user());
    }
}
