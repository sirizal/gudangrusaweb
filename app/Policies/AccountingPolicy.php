<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

abstract class AccountingPolicy
{
    /**
     * Any user holding at least one accounting role can view records.
     */
    protected function isStaff(User $user): bool
    {
        return $user->hasRole(
            Role::SuperAdmin,
            Role::FinanceManager,
            Role::Accountant,
            Role::BudgetOwner,
            Role::BudgetApprover,
            Role::Viewer,
        );
    }

    /**
     * Accountants and above can manage master data and create journals.
     */
    protected function canManage(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin, Role::FinanceManager, Role::Accountant);
    }

    /**
     * Finance managers and above can approve, post, reverse and close periods.
     */
    protected function isFinance(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin, Role::FinanceManager);
    }
}
