<?php

namespace App\Policies;

use App\Models\FiscalYear;
use App\Models\User;

class FiscalYearPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->canManage($user);
    }

    public function generatePeriods(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->canManage($user);
    }

    public function closeYear(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->isFinance($user);
    }
}
