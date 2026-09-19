<?php

namespace App\Filament\Wms\Resources\StockAdjustments\Pages;

use App\Filament\Wms\Resources\StockAdjustments\StockAdjustmentResource;
use App\Services\Wms\StockAdjustmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(StockAdjustmentService::class)->adjust($data, auth()->user());
    }
}
