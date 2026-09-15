<?php

namespace App\Filament\Sales\Resources\SalesOrders\Pages;

use App\Filament\Sales\Resources\SalesOrders\SalesOrderResource;
use App\Services\Sales\SalesOrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SalesOrderService::class)->create($data, auth()->user());
    }
}
