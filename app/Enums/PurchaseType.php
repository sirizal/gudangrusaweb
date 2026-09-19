<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PurchaseType: string implements HasLabel
{
    case Inventory = 'inventory';

    case General = 'general';

    case Capex = 'capex';

    public function getLabel(): string
    {
        return match ($this) {
            self::Inventory => 'Inventory Item',
            self::General => 'General Purchase',
            self::Capex => 'CAPEX / Asset',
        };
    }

    /**
     * Inventory lines are received by the warehouse; general and capex on the purchasing panel.
     */
    public function isReceivedHere(): bool
    {
        return $this !== self::Inventory;
    }
}
