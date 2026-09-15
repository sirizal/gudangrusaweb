<?php

namespace App\Policies;

use App\Models\AccountingPeriod;
use App\Models\User;

class AccountingPeriodPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, AccountingPeriod $period): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, AccountingPeriod $period): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, AccountingPeriod $period): bool
    {
        return $this->canManage($user);
    }

    public function close(User $user, AccountingPeriod $period): bool
    {
        return $this->isFinance($user);
    }

    public function reopen(User $user, AccountingPeriod $period): bool
    {
        return $this->isFinance($user);
    }
}
