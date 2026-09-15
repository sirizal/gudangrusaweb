<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasLabel
{
    case Issued = 'issued';

    case PartiallyPaid = 'partially_paid';

    case Paid = 'paid';

    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Issued => 'Issued',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }
}
