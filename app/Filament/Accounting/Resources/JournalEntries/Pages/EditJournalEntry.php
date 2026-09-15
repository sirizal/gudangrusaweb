<?php

namespace App\Filament\Accounting\Resources\JournalEntries\Pages;

use App\Enums\JournalStatus;
use App\Filament\Accounting\Resources\JournalEntries\Concerns\HandlesJournalWorkflow;
use App\Filament\Accounting\Resources\JournalEntries\JournalEntryResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditJournalEntry extends EditRecord
{
    use HandlesJournalWorkflow;

    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return $this->getJournalWorkflowActions();
    }

    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        if (in_array($this->record->status, [JournalStatus::Posted, JournalStatus::Reversed], true)) {
            return $schema->disabled();
        }

        return $schema;
    }
}
