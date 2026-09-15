<?php

namespace App\Policies;

use App\Models\OpeningBalance;
use App\Models\User;

class OpeningBalancePolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, OpeningBalance $openingBalance): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, OpeningBalance $openingBalance): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, OpeningBalance $openingBalance): bool
    {
        return $this->canManage($user);
    }

    public function generate(User $user): bool
    {
        return $this->isFinance($user);
    }
}
