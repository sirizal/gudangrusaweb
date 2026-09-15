<?php

namespace App\Filament\Accounting\Resources\OpeningBalances\Pages;

use App\Filament\Accounting\Resources\OpeningBalances\OpeningBalanceResource;
use App\Models\FiscalYear;
use App\Services\Accounting\AccountingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

class ListOpeningBalances extends ListRecords
{
    protected static string $resource = OpeningBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            $this->buildGenerateJournalAction(),
        ];
    }

    private function buildGenerateJournalAction(): Action
    {
        return Action::make('generateJournal')
            ->label('Generate opening balance journal')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('success')
            ->requiresConfirmation()
            ->form([
                Select::make('fiscal_year_id')
                    ->label('Fiscal year')
                    ->options(FiscalYear::query()->orderBy('start_date')->get()->mapWithKeys(fn (FiscalYear $year): array => [$year->id => $year->name]))
                    ->required(),
            ])
            ->action(function (array $data): void {
                try {
                    $journal = app(AccountingService::class)->createOpeningBalance(
                        FiscalYear::findOrFail($data['fiscal_year_id']),
                        auth()->user(),
                    );

                    Notification::make()
                        ->title('Opening balance journal '.$journal->journal_number.' created and posted.')
                        ->success()
                        ->send();
                } catch (InvalidArgumentException $e) {
                    Notification::make()
                        ->title($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
