<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    case SuperAdmin = 'super_admin';

    case FinanceManager = 'finance_manager';

    case Accountant = 'accountant';

    case BudgetOwner = 'budget_owner';

    case BudgetApprover = 'budget_approver';

    case Purchasing = 'purchasing';

    case Warehouse = 'warehouse';

    case Viewer = 'viewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::FinanceManager => 'Finance Manager',
            self::Accountant => 'Accountant',
            self::BudgetOwner => 'Budget Owner',
            self::BudgetApprover => 'Budget Approver',
            self::Purchasing => 'Purchasing',
            self::Warehouse => 'Warehouse',
            self::Viewer => 'Viewer',
        };
    }
}
