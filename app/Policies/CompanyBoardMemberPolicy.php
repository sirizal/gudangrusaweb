<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CompanyBoardMember;
use App\Models\User;

class CompanyBoardMemberPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, CompanyBoardMember $member): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }

    public function update(User $user, CompanyBoardMember $member): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }

    public function delete(User $user, CompanyBoardMember $member): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }
}
