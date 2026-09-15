<?php

namespace App\Filament\Accounting\Resources\FinancialStatementLines\Pages;

use App\Filament\Accounting\Resources\FinancialStatementLines\FinancialStatementLineResource;
use Filament\Resources\Pages\EditRecord;

class EditFinancialStatementLine extends EditRecord
{
    protected static string $resource = FinancialStatementLineResource::class;
}
