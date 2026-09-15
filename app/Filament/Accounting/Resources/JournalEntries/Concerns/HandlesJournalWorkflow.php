<?php

namespace App\Filament\Accounting\Resources\JournalEntries\Concerns;

use App\Services\Accounting\AccountingService;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

trait HandlesJournalWorkflow
{
    /**
     * @return array<int, Action>
     */
    protected function getJournalWorkflowActions(): array
    {
        return [
            $this->buildSubmitAction(),
            $this->buildApproveAction(),
            $this->buildPostAction(),
            $this->buildReverseAction(),
            $this->buildCancelAction(),
        ];
    }

    private function buildSubmitAction(): Action
    {
        return Action::make('submit')
            ->label('Submit')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->visible(fn (): bool => auth()->user()?->can('submit', $this->record) ?? false)
            ->action(function (): void {
                $this->runServiceAction(fn (): string => 'Journal submitted', fn (AccountingService $service) => $service->submit($this->record));
            });
    }

    private function buildApproveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('warning')
            ->visible(fn (): bool => auth()->user()?->can('approve', $this->record) ?? false)
            ->action(function (): void {
                $this->runServiceAction(fn (): string => 'Journal approved', fn (AccountingService $service) => $service->approve($this->record, auth()->user()));
            });
    }

    private function buildPostAction(): Action
    {
        return Action::make('post')
            ->label('Post')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (): bool => auth()->user()?->can('post', $this->record) ?? false)
            ->action(function (): void {
                $this->runServiceAction(fn (): string => 'Journal posted', fn (AccountingService $service) => $service->post($this->record, auth()->user()));
            });
    }

    private function buildReverseAction(): Action
    {
        return Action::make('reverse')
            ->label('Reverse')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('A reversing journal entry will be created and posted immediately.')
            ->visible(fn (): bool => auth()->user()?->can('reverse', $this->record) ?? false)
            ->action(function (): void {
                $this->runServiceAction(fn (): string => 'Journal reversed', fn (AccountingService $service) => $service->reverse($this->record, auth()->user()));
            });
    }

    private function buildCancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (): bool => auth()->user()?->can('cancel', $this->record) ?? false)
            ->action(function (): void {
                $this->runServiceAction(fn (): string => 'Journal cancelled', fn (AccountingService $service) => $service->cancel($this->record));
            });
    }

    private function runServiceAction(Closure $successMessage, Closure $serviceCall): void
    {
        try {
            $serviceCall(app(AccountingService::class));
            Notification::make()
                ->title($successMessage())
                ->success()
                ->send();
            $this->fillForm();
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
