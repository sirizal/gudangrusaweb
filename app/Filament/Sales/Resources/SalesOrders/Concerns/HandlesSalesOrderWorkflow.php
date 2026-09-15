<?php

namespace App\Filament\Sales\Resources\SalesOrders\Concerns;

use App\Services\Sales\SalesOrderService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

trait HandlesSalesOrderWorkflow
{
    /**
     * @return array<int, Action>
     */
    protected function getSalesOrderWorkflowActions(): array
    {
        return [
            Action::make('advance')
                ->label(fn (): string => 'Advance to '.($this->record->status->next()[0]?->getLabel() ?? 'next'))
                ->icon(Heroicon::OutlinedForward)
                ->visible(fn (): bool => $this->record->status->next() !== [])
                ->action(function (): void {
                    try {
                        $order = app(SalesOrderService::class)->advance($this->record, auth()->user());

                        Notification::make()
                            ->title('Order advanced to '.$order->status->getLabel())
                            ->success()
                            ->send();

                        $this->fillForm();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('cancel-order')
                ->label('Cancel order')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! $this->record->status->isTerminal())
                ->action(function (): void {
                    try {
                        app(SalesOrderService::class)->cancel($this->record, auth()->user());

                        Notification::make()->title('Order cancelled')->success()->send();
                        $this->fillForm();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
