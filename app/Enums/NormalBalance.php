<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NormalBalance: string implements HasLabel
{
    case Debit = 'debit';

    case Credit = 'credit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Debit => 'Debit',
            self::Credit => 'Credit',
        };
    }

    public function isDebit(): bool
    {
        return $this === self::Debit;
    }
}
