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

    protected function isLeader(User $user, Society $society): bool
    {
        if (!$user->member_id) return false;

        return \App\Models\SocietyMandate::where('society_id', $society->id)
            ->where('year', date('Y'))
            ->where('status', 'active')
            ->whereHas('roles', function($q) use ($user) {
                $q->where('member_id', $user->member_id)
                  ->where('role_type', 'board');
            })->exists();
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('societies.index') || $user->member_id !== null;
    }

    public function view(User $user, Society $society): bool
    {
        return $user->hasPermission('societies.show') || $this->isLeader($user, $society);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('societies.store');
    }

    public function update(User $user, Society $society): bool
    {
        return $user->hasPermission('societies.update') || $this->isLeader($user, $society);
    }

    public function delete(User $user, Society $society): bool
    {
        return $user->hasPermission('societies.destroy');
    }
}
