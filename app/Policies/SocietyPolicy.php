<?php

namespace App\Policies;

use App\Models\Society;
use App\Models\User;

class SocietyPolicy
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
        return $user->hasPermission('societies.index');
    }

    public function view(User $user, Society $society): bool
    {
        return $user->hasPermission('societies.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('societies.store');
    }

    public function update(User $user, Society $society): bool
    {
        return $user->hasPermission('societies.update');
    }

    public function delete(User $user, Society $society): bool
    {
        return $user->hasPermission('societies.destroy');
    }
}
