<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SalesOrderStatus: string implements HasLabel
{
    case Raised = 'raised';

    case Open = 'open';

    case OnPick = 'on_pick';

    case OnPack = 'on_pack';

    case OnDelivery = 'on_delivery';

    case Delivered = 'delivered';

    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Raised => 'Raised',
            self::Open => 'Open',
            self::OnPick => 'On Pick',
            self::OnPack => 'On Pack',
            self::OnDelivery => 'On Delivery',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The statuses an order may transition to next.
     *
     * @return array<int, SalesOrderStatus>
     */
    public function next(): array
    {
        return match ($this) {
            self::Raised => [self::Open],
            self::Open => [self::OnPick],
            self::OnPick => [self::OnPack],
            self::OnPack => [self::OnDelivery],
            self::OnDelivery => [self::Delivered],
            self::Delivered => [],
            self::Cancelled => [],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], true);
    }
}
