<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roles.index');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('roles.store');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.destroy');
    }
}
