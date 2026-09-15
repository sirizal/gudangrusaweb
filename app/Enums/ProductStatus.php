<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasLabel
{
    case Draft = 'draft';

    case Active = 'active';

    case Inactive = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}
