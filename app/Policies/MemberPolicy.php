<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    /**
     * Admin bypass global
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('members.index');
    }

    public function view(User $user, Member $member): bool
    {
        return $user->hasPermission('members.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('members.store');
    }

    public function update(User $user, Member $member): bool
    {
        return $user->hasPermission('members.update');
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->hasPermission('members.destroy');
    }
}
