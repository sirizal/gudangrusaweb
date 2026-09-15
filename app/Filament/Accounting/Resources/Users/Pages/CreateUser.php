<?php

namespace App\Filament\Accounting\Resources\Users\Pages;

use App\Filament\Accounting\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
