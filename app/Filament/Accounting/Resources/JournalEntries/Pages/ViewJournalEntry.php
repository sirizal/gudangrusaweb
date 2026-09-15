<?php

namespace App\Filament\Accounting\Resources\JournalEntries\Pages;

use App\Filament\Accounting\Resources\JournalEntries\Concerns\HandlesJournalWorkflow;
use App\Filament\Accounting\Resources\JournalEntries\JournalEntryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewJournalEntry extends ViewRecord
{
    use HandlesJournalWorkflow;

    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return $this->getJournalWorkflowActions();
    }
}
