<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VendorBillStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Posted = 'posted';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Posted => 'Posted',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }
}
