<?php

namespace App\Filament\Purchasing\Resources\PurchaseRequests\Concerns;

use App\Enums\PurchaseRequestStatus;
use App\Services\Purchasing\PurchaseRequestService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

trait HandlesPurchaseRequestWorkflow
{
    /**
     * @return array<int, Action>
     */
    protected function getPurchaseRequestWorkflowActions(): array
    {
        return [
            Action::make('submit')
                ->label('Submit')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn (): bool => $this->record->status->isEditable())
                ->action(fn () => $this->runRequest(fn () => app(PurchaseRequestService::class)->submit($this->record), 'Request submitted')),
            Action::make('approve')
                ->label('Approve')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === PurchaseRequestStatus::Submitted)
                ->action(fn () => $this->runRequest(fn () => app(PurchaseRequestService::class)->approve($this->record, auth()->user()), 'Request approved')),
            Action::make('reject')
                ->label('Reject')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === PurchaseRequestStatus::Submitted)
                ->action(fn () => $this->runRequest(fn () => app(PurchaseRequestService::class)->reject($this->record), 'Request rejected')),
            Action::make('convert')
                ->label('Convert to PO')
                ->icon(Heroicon::OutlinedArrowRightCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('A purchase order will be created from this request and confirmed.')
                ->visible(fn (): bool => $this->record->status === PurchaseRequestStatus::Approved)
                ->action(fn () => $this->runRequest(fn () => app(PurchaseRequestService::class)->convertToOrder($this->record, auth()->user()), 'Purchase order created')),
        ];
    }

    private function runRequest(callable $call, string $success): void
    {
        try {
            $call();
            Notification::make()->title($success)->success()->send();
            $this->fillForm();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
