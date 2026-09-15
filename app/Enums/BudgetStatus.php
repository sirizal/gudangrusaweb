<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BudgetStatus: string implements HasLabel
{
    case Draft = 'draft';

    case Submitted = 'submitted';

    case Approved = 'approved';

    case Rejected = 'rejected';

    case Locked = 'locked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Locked => 'Locked',
        };
    }
}
