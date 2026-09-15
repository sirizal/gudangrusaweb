<?php

namespace App\Filament\Accounting\Resources\Users\Pages;

use App\Filament\Accounting\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => auth()->id() === $this->record->id),
        ];
    }
}
