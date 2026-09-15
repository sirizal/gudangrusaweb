<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, Company $company): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }
}
