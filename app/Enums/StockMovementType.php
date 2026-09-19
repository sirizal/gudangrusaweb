<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StockMovementType: string implements HasLabel
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';

    public function getLabel(): string
    {
        return match ($this) {
            self::Inbound => 'Inbound',
            self::Outbound => 'Outbound',
            self::TransferIn => 'Transfer In',
            self::TransferOut => 'Transfer Out',
            self::AdjustmentIn => 'Adjustment In',
            self::AdjustmentOut => 'Adjustment Out',
        };
    }
}
