<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FiscalYearStatus: string implements HasLabel
{
    case Open = 'open';

    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Closed => 'Closed',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Open;
    }
}
