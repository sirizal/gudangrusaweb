<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CompanyDocument;
use App\Models\User;

class CompanyDocumentPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, CompanyDocument $document): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }

    public function update(User $user, CompanyDocument $document): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }

    public function delete(User $user, CompanyDocument $document): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }
}
