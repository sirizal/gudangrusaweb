<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyBoardPosition: string implements HasLabel
{
    case PresidentDirector = 'president_director';

    case Director = 'director';

    case FinanceDirector = 'finance_director';

    case PresidentCommissioner = 'president_commissioner';

    case Commissioner = 'commissioner';

    case IndependentCommissioner = 'independent_commissioner';

    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::PresidentDirector => 'Presiden Direktur',
            self::Director => 'Direktur',
            self::FinanceDirector => 'Direktur Keuangan',
            self::PresidentCommissioner => 'Komisaris Utama',
            self::Commissioner => 'Komisaris',
            self::IndependentCommissioner => 'Komisaris Independen',
            self::Other => 'Lainnya',
        };
    }
}
