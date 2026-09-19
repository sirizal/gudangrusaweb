<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PurchaseRequestStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Converted = 'converted';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Converted => 'Converted to PO',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }
}
