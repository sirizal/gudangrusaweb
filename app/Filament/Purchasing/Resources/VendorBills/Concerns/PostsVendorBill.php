<?php

namespace App\Filament\Purchasing\Resources\VendorBills\Concerns;

use App\Enums\VendorBillStatus;
use App\Services\Purchasing\VendorBillService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

trait PostsVendorBill
{
    /**
     * @return array<int, Action>
     */
    protected function getVendorBillActions(): array
    {
        return [
            Action::make('post')
                ->label('Post bill')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === VendorBillStatus::Draft)
                ->action(function (): void {
                    try {
                        app(VendorBillService::class)->post($this->record, auth()->user());

                        Notification::make()->title('Bill posted and journal created')->success()->send();
                        $this->fillForm();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
