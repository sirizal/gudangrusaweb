<?php

namespace App\Filament\Accounting\Resources\FiscalYears\Pages;

use App\Filament\Accounting\Resources\FiscalYears\FiscalYearResource;
use App\Services\Accounting\PeriodService;
use App\Services\Accounting\YearEndService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

class EditFiscalYear extends EditRecord
{
    protected static string $resource = FiscalYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generatePeriods')
                ->label('Generate 12 Periods')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->requiresConfirmation()
                ->authorize(fn (): bool => auth()->user()->can('generatePeriods', $this->record))
                ->action(function (): void {
                    try {
                        app(PeriodService::class)->generateForFiscalYear($this->record);
                        Notification::make()
                            ->title('Accounting periods generated')
                            ->success()
                            ->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make(),
            $this->buildCloseYearAction(),
        ];
    }

    private function buildCloseYearAction(): Action
    {
        return Action::make('closeYear')
            ->label('Close Fiscal Year')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (): bool => $this->record->status->isOpen())
            ->authorize(fn (): bool => auth()->user()->can('closeYear', $this->record))
            ->modalIcon(Heroicon::OutlinedLockClosed)
            ->modalHeading(fn (): string => 'Close '.$this->record->name.'?')
            ->modalDescription(new HtmlString(
                'This posts the year-end closing journal (moving the year\'s net result into the current-year-profit equity account), '
                .'closes the fiscal year, creates the next fiscal year with its periods, and carries forward opening balances. '
                .'It cannot be undone.'
            ))
            ->action(function (): void {
                try {
                    $result = app(YearEndService::class)->closeFiscalYear($this->record, auth()->user());

                    Notification::make()
                        ->title('Fiscal year closed')
                        ->body(
                            'Closing journal '.$result['closing_journal']->journal_number
                            .' posted. '.$result['next_fiscal_year']->name
                            .' created with '.$result['opening_balances_created'].' opening balances carried forward.'
                        )
                        ->success()
                        ->send();

                    $this->redirect(FiscalYearResource::getUrl('edit', ['record' => $result['next_fiscal_year']]));
                } catch (InvalidArgumentException $e) {
                    Notification::make()
                        ->title($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
