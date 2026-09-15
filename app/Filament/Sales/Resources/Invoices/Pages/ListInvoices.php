<?php

namespace App\Filament\Sales\Resources\Invoices\Pages;

use App\Filament\Sales\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
}
