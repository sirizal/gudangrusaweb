<?php

namespace App\Filament\Purchasing\Resources\GoodsReceipts\Pages;

use App\Filament\Purchasing\Resources\GoodsReceipts\GoodsReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceipts extends ListRecords
{
    protected static string $resource = GoodsReceiptResource::class;
}
