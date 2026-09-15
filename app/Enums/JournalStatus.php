<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JournalStatus: string implements HasLabel
{
    case Draft = 'draft';

    case Submitted = 'submitted';

    case Approved = 'approved';

    case Posted = 'posted';

    case Reversed = 'reversed';

    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Posted => 'Posted',
            self::Reversed => 'Reversed',
            self::Cancelled => 'Cancelled',
        };
    }
}
