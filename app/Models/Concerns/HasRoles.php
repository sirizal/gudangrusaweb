<?php

namespace App\Models\Concerns;

use App\Enums\Role as RoleEnum;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Determine whether the user holds any of the given roles.
     */
    public function hasRole(RoleEnum|string ...$roles): bool
    {
        $codes = array_map(
            fn (RoleEnum|string $role): string => $role instanceof RoleEnum ? $role->value : $role,
            $roles,
        );

        return $this->roles()->whereIn('code', $codes)->exists();
    }
}
