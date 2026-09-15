<?php

namespace App\Policies;

use App\Models\CostCenter;
use App\Models\User;

class CostCenterPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, CostCenter $costCenter): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, CostCenter $costCenter): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, CostCenter $costCenter): bool
    {
        return $this->canManage($user);
    }
}
