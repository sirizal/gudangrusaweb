<?php

namespace App\Filament\Accounting\Resources\AuditLogs\Pages;

use App\Filament\Accounting\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;
}
