<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, Account $account): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Account $account): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Account $account): bool
    {
        return $this->canManage($user);
    }
}
