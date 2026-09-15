<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->is($model) ? false : $this->canManage($user);
    }
}
