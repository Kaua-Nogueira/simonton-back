<?php

namespace App\Policies;

use App\Models\CostCenter;
use App\Models\User;

class CostCenterPolicy
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
        return $user->hasPermission('cost-centers.index');
    }

    public function view(User $user, CostCenter $costCenter): bool
    {
        return $user->hasPermission('cost-centers.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('cost-centers.store');
    }

    public function update(User $user, CostCenter $costCenter): bool
    {
        return $user->hasPermission('cost-centers.update');
    }

    public function delete(User $user, CostCenter $costCenter): bool
    {
        return $user->hasPermission('cost-centers.destroy');
    }
}
