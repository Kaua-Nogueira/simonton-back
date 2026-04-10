<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
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
        return $user->hasPermission('reports.view')
            || $user->hasPermission('reports.dizimos')
            || $user->hasPermission('reports.church.balancete');
    }

    public function view(User $user, ?Report $report = null): bool
    {
        return $this->viewAny($user);
    }
}
