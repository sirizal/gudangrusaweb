<?php

namespace App\Filament\Accounting\Resources\Budgets\Concerns;

use App\Filament\Accounting\Resources\Budgets\Pages\EditBudget;
use App\Services\Accounting\BudgetService;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

trait HandlesBudgetWorkflow
{
    /**
     * @return array<int, Action>
     */
    protected function getBudgetWorkflowActions(): array
    {
        return [
            $this->makeSubmitAction(),
            $this->makeApproveAction(),
            $this->makeRejectAction(),
            $this->makeLockAction(),
            $this->makeActivateAction(),
            $this->makeReviseAction(),
        ];
    }

    private function makeSubmitAction(): Action
    {
        return Action::make('submit')
            ->label('Submit')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->visible(fn (): bool => auth()->user()?->can('submit', $this->record) ?? false)
            ->action(function (): void {
                $this->runBudgetAction(fn () => app(BudgetService::class)->submit($this->record, auth()->user()), 'Budget submitted');
            });
    }

    private function makeApproveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (): bool => auth()->user()?->can('approve', $this->record) ?? false)
            ->action(function (): void {
                $this->runBudgetAction(fn () => app(BudgetService::class)->approve($this->record, auth()->user()), 'Budget approved and activated');
            });
    }

    private function makeRejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (): bool => auth()->user()?->can('reject', $this->record) ?? false)
            ->action(function (): void {
                $this->runBudgetAction(fn () => app(BudgetService::class)->reject($this->record, auth()->user()), 'Budget rejected');
            });
    }

    private function makeLockAction(): Action
    {
        return Action::make('lock')
            ->label('Lock')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (): bool => auth()->user()?->can('lock', $this->record) ?? false)
            ->action(function (): void {
                $this->runBudgetAction(fn () => app(BudgetService::class)->lock($this->record, auth()->user()), 'Budget locked');
            });
    }

    private function makeActivateAction(): Action
    {
        return Action::make('activate')
            ->label('Set active')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (): bool => auth()->user()?->can('activate', $this->record) ?? false)
            ->action(function (): void {
                $this->runBudgetAction(fn () => app(BudgetService::class)->activate($this->record), 'Budget set as active');
            });
    }

    private function makeReviseAction(): Action
    {
        return Action::make('revise')
            ->label('Create revision')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('A new draft version will be created that copies the current lines.')
            ->visible(fn (): bool => auth()->user()?->can('revise', $this->record) ?? false)
            ->action(function (): void {
                try {
                    $revision = app(BudgetService::class)->revise($this->record, auth()->user());

                    Notification::make()
                        ->title('Revision '.$revision->version.' created')
                        ->success()
                        ->send();

                    $this->redirect(EditBudget::getUrl(['record' => $revision]));
                } catch (InvalidArgumentException $e) {
                    Notification::make()
                        ->title($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function runBudgetAction(Closure $call, string $successMessage): void
    {
        try {
            $call();
            Notification::make()
                ->title($successMessage)
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
