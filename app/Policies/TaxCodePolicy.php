<?php

namespace App\Policies;

use App\Models\TaxCode;
use App\Models\User;

class TaxCodePolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, TaxCode $taxCode): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, TaxCode $taxCode): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, TaxCode $taxCode): bool
    {
        return $this->canManage($user);
    }
}
