<?php

namespace App\Filament\Accounting\Resources\JournalEntries\Pages;

use App\Filament\Accounting\Resources\JournalEntries\JournalEntryResource;
use App\Services\Accounting\AccountingService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    public function handleRecordCreation(array $data): Model
    {
        return app(AccountingService::class)->createJournal($data, auth()->user());
    }

    protected function saveRelationships(): void
    {
        // Journal lines are persisted by AccountingService::createJournal.
    }
}
