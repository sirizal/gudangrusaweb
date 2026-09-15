<?php

namespace App\Policies;

use App\Enums\BudgetStatus;
use App\Enums\Role;
use App\Models\Budget;
use App\Models\User;

class BudgetPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, Budget $budget): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageBudget($user);
    }

    public function update(User $user, Budget $budget): bool
    {
        return $this->canManageBudget($user)
            && in_array($budget->status, [BudgetStatus::Draft, BudgetStatus::Rejected], true);
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $this->canManageBudget($user)
            && in_array($budget->status, [BudgetStatus::Draft, BudgetStatus::Rejected], true);
    }

    public function submit(User $user, Budget $budget): bool
    {
        return $this->canManageBudget($user)
            && in_array($budget->status, [BudgetStatus::Draft, BudgetStatus::Rejected], true);
    }

    public function approve(User $user, Budget $budget): bool
    {
        return $this->canApproveBudget($user) && $budget->status === BudgetStatus::Submitted;
    }

    public function reject(User $user, Budget $budget): bool
    {
        return $this->canApproveBudget($user) && $budget->status === BudgetStatus::Submitted;
    }

    public function lock(User $user, Budget $budget): bool
    {
        return $this->canApproveBudget($user) && $budget->status === BudgetStatus::Approved;
    }

    public function activate(User $user, Budget $budget): bool
    {
        return $this->canApproveBudget($user)
            && in_array($budget->status, [BudgetStatus::Approved, BudgetStatus::Locked], true)
            && ! $budget->is_active;
    }

    public function revise(User $user, Budget $budget): bool
    {
        return $this->canApproveBudget($user)
            && in_array($budget->status, [BudgetStatus::Approved, BudgetStatus::Locked], true);
    }

    protected function canManageBudget(User $user): bool
    {
        return $user->hasRole(Role::BudgetOwner, Role::Accountant, Role::FinanceManager, Role::SuperAdmin);
    }

    protected function canApproveBudget(User $user): bool
    {
        return $user->hasRole(Role::BudgetApprover, Role::FinanceManager, Role::SuperAdmin);
    }
}
