<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Models\Concerns\HasRoles;
use App\Models\Role as RoleModel;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'accounting') {
            return $this->hasRole(Role::SuperAdmin, Role::FinanceManager, Role::Accountant, Role::BudgetOwner, Role::BudgetApprover, Role::Viewer);
        }

        if ($panel->getId() === 'geography') {
            return $this->hasRole(Role::SuperAdmin);
        }

        if ($panel->getId() === 'sales') {
            return $this->hasRole(Role::SuperAdmin, Role::FinanceManager, Role::Accountant);
        }

        // Admin, Products, and any other panel are open to authenticated users.
        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
