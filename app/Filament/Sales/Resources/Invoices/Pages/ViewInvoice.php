<?php

namespace App\Filament\Sales\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Sales\Resources\Invoices\InvoiceResource;
use App\Services\Sales\SalesInvoiceService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('record-payment')
                ->label('Record payment')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->visible(fn (): bool => $this->record->status !== InvoiceStatus::Cancelled && ! $this->record->isFullyPaid())
                ->form([
                    DatePicker::make('payment_date')
                        ->required()
                        ->default(now()),
                    TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->maxValue(fn (): float => max(0, round((float) $this->record->total - (float) $this->record->paid_amount, 2))),
                    TextInput::make('reference')
                        ->maxLength(100),
                ])
                ->action(function (array $data): void {
                    try {
                        app(SalesInvoiceService::class)->recordPayment(
                            $this->record,
                            (float) $data['amount'],
                            $data,
                            auth()->user(),
                        );

                        Notification::make()
                            ->title('Payment recorded')
                            ->success()
                            ->send();

                        $this->fillForm();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('cancel-invoice')
                ->label('Cancel invoice')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== InvoiceStatus::Cancelled && ! $this->record->isFullyPaid())
                ->action(function (): void {
                    try {
                        app(SalesInvoiceService::class)->cancel($this->record, auth()->user());

                        Notification::make()->title('Invoice cancelled')->success()->send();
                        $this->fillForm();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
