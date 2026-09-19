<?php

namespace App\Filament\Purchasing\Resources\PurchaseOrders\Concerns;

use App\Enums\PurchaseOrderStatus;
use App\Services\Purchasing\GoodsReceiptService;
use App\Services\Purchasing\PurchaseOrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

trait HandlesPurchaseOrderWorkflow
{
    /**
     * @return array<int, Action>
     */
    protected function getPurchaseOrderWorkflowActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Confirm order')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->visible(fn (): bool => $this->record->status === PurchaseOrderStatus::Draft)
                ->action(fn () => $this->runOrder(fn () => app(PurchaseOrderService::class)->confirm($this->record), 'Purchase order confirmed')),
            $this->buildReceiveAction(),
            Action::make('close')
                ->label('Close')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! $this->record->status->isTerminal() && $this->record->status !== PurchaseOrderStatus::Draft)
                ->action(fn () => $this->runOrder(fn () => app(PurchaseOrderService::class)->close($this->record), 'Purchase order closed')),
            Action::make('cancel-order')
                ->label('Cancel order')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! in_array($this->record->status, [PurchaseOrderStatus::Received, PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled], true))
                ->action(fn () => $this->runOrder(fn () => app(PurchaseOrderService::class)->cancel($this->record), 'Purchase order cancelled')),
        ];
    }

    private function buildReceiveAction(): Action
    {
        return Action::make('receive')
            ->label('Receive (goods receipt)')
            ->icon(Heroicon::OutlinedInboxArrowDown)
            ->color('success')
            ->visible(fn (): bool => in_array($this->record->status, [PurchaseOrderStatus::Open, PurchaseOrderStatus::PartiallyReceived], true)
                && $this->record->lines->contains(fn ($line): bool => $line->purchase_type->isReceivedHere() && $line->received_quantity < $line->quantity))
            ->form(function (): array {
                $lines = $this->record->lines
                    ->filter(fn ($line): bool => $line->purchase_type->isReceivedHere() && $line->received_quantity < $line->quantity);

                return [
                    DatePicker::make('receipt_date')->required()->default(now()),
                    Repeater::make('lines')
                        ->label('Receive these lines')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Hidden::make('purchase_order_line_id'),
                            TextInput::make('line_label')->label('Line')->disabled()->dehydrated(false),
                            TextInput::make('remaining')->label('Remaining')->disabled()->dehydrated(false),
                            TextInput::make('quantity_received')->label('Receive now')->numeric()->required()->minValue(0),
                        ])
                        ->default($lines->map(fn ($line): array => [
                            'purchase_order_line_id' => $line->id,
                            'line_label' => $line->description ?: ($line->product?->name ?? 'Line #'.$line->id),
                            'remaining' => (string) ($line->quantity - $line->received_quantity),
                            'quantity_received' => $line->quantity - $line->received_quantity,
                        ])->values()->all()),
                ];
            })
            ->action(function (array $data): void {
                try {
                    app(GoodsReceiptService::class)->receive($this->record, $data, auth()->user());

                    Notification::make()->title('Goods received and journal posted')->success()->send();
                    $this->fillForm();
                } catch (InvalidArgumentException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }

    private function runOrder(callable $call, string $success): void
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
