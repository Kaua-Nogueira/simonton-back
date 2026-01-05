<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
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
        return $user->hasPermission('meetings.index');
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('meetings.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('meetings.store');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('meetings.update');
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('meetings.destroy');
    }

    public function populateAttendance(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('meetings.update'); // Using update permission for this action
    }

    public function pdf(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('meetings.show'); // Same as view
    }
}
