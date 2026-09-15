<?php

namespace App\Policies;

use App\Models\FinancialStatementLine;
use App\Models\User;

class FinancialStatementLinePolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, FinancialStatementLine $line): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, FinancialStatementLine $line): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, FinancialStatementLine $line): bool
    {
        return $this->canManage($user);
    }
}
