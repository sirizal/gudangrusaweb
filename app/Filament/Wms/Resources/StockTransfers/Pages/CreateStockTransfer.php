<?php

namespace App\Filament\Wms\Resources\StockTransfers\Pages;

use App\Filament\Wms\Resources\StockTransfers\StockTransferResource;
use App\Services\Wms\StockTransferService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(StockTransferService::class)->transfer($data, auth()->user());
    }
}
