<?php

namespace App\Policies;

use App\Models\PaymentTerm;
use App\Models\User;

class PaymentTermPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, PaymentTerm $term): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, PaymentTerm $term): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, PaymentTerm $term): bool
    {
        return $this->canManage($user);
    }
}
