<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;

class CurrencyPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, Currency $currency): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Currency $currency): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Currency $currency): bool
    {
        return $this->canManage($user);
    }
}
