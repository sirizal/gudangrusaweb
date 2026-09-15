<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BrandStatus: string implements HasLabel
{
    case Active = 'active';

    case Inactive = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}
