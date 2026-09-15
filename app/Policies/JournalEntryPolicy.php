<?php

namespace App\Policies;

use App\Enums\JournalStatus;
use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy extends AccountingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, JournalEntry $entry): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, JournalEntry $entry): bool
    {
        return $this->canManage($user) && ! in_array($entry->status, [JournalStatus::Posted, JournalStatus::Reversed], true);
    }

    public function delete(User $user, JournalEntry $entry): bool
    {
        return $this->canManage($user) && in_array($entry->status, [JournalStatus::Draft, JournalStatus::Submitted, JournalStatus::Cancelled], true);
    }

    public function submit(User $user, JournalEntry $entry): bool
    {
        return $this->canManage($user) && $entry->status === JournalStatus::Draft;
    }

    public function approve(User $user, JournalEntry $entry): bool
    {
        return $this->isFinance($user) && $entry->status === JournalStatus::Submitted;
    }

    public function post(User $user, JournalEntry $entry): bool
    {
        return $this->canManage($user) && $entry->status === JournalStatus::Approved;
    }

    public function reverse(User $user, JournalEntry $entry): bool
    {
        return $this->isFinance($user) && $entry->status === JournalStatus::Posted && ! $entry->is_reversed;
    }

    public function cancel(User $user, JournalEntry $entry): bool
    {
        return $this->canManage($user) && in_array($entry->status, [JournalStatus::Draft, JournalStatus::Submitted], true);
    }
}
