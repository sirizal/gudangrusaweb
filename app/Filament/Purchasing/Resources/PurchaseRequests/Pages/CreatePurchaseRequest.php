<?php

namespace App\Filament\Purchasing\Resources\PurchaseRequests\Pages;

use App\Filament\Purchasing\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Services\Purchasing\PurchaseRequestService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PurchaseRequestService::class)->create($data, auth()->user());
    }
}
