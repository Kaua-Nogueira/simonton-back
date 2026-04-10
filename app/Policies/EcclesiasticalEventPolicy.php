<?php

namespace App\Policies;

use App\Models\EcclesiasticalEvent;
use App\Models\EventAssignment;
use App\Models\User;

class EcclesiasticalEventPolicy
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
        return $user->hasPermission('calendar.events.index');
    }

    public function view(User $user, EcclesiasticalEvent $event): bool
    {
        return $user->hasPermission('calendar.events.show')
            || $user->hasPermission('calendar.events.index');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('calendar.events.store');
    }

    public function update(User $user, EcclesiasticalEvent $event): bool
    {
        return $user->hasPermission('calendar.events.update');
    }

    public function delete(User $user, EcclesiasticalEvent $event): bool
    {
        return $user->hasPermission('calendar.events.destroy');
    }

    public function manageAssignments(User $user, EcclesiasticalEvent $event): bool
    {
        return $user->hasPermission('calendar.events.assignments.store')
            || $user->hasPermission('calendar.events.assignments.update')
            || $user->hasPermission('calendar.events.assignments.destroy');
    }

    public function respondAssignment(User $user, EventAssignment $assignment): bool
    {
        if ($user->hasPermission('calendar.assignments.respond')) {
            return true;
        }

        if (!$user->member_id) {
            return false;
        }

        return (int) $assignment->member_id === (int) $user->member_id;
    }
}
