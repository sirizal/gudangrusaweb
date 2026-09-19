<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WarehouseLocationType: string implements HasLabel
{
    case Receiving = 'receiving';
    case Storage = 'storage';
    case Picking = 'picking';
    case Shipping = 'shipping';
    case Staging = 'staging';
    case Quarantine = 'quarantine';

    public function getLabel(): string
    {
        return match ($this) {
            self::Receiving => 'Receiving',
            self::Storage => 'Storage',
            self::Picking => 'Picking',
            self::Shipping => 'Shipping',
            self::Staging => 'Staging',
            self::Quarantine => 'Quarantine',
        };
    }
}
