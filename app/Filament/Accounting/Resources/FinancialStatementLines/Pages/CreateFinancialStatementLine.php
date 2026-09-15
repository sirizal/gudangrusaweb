<?php

namespace App\Filament\Accounting\Resources\FinancialStatementLines\Pages;

use App\Filament\Accounting\Resources\FinancialStatementLines\FinancialStatementLineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialStatementLine extends CreateRecord
{
    protected static string $resource = FinancialStatementLineResource::class;
}
