<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CashFlowActivity: string implements HasLabel
{
    case Operating = 'operating';

    case Investing = 'investing';

    case Financing = 'financing';

    public function getLabel(): string
    {
        return match ($this) {
            self::Operating => 'Operating',
            self::Investing => 'Investing',
            self::Financing => 'Financing',
        };
    }
}
