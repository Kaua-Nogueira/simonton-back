<?php

namespace App\Policies;

use App\Models\ExpenseReconciliation;
use App\Models\User;

class ExpenseReconciliationPolicy
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
        return $user->hasPermission('finance.reconciliations.index');
    }

    public function view(User $user, ExpenseReconciliation $reconciliation): bool
    {
        return $user->hasPermission('finance.reconciliations.show')
            || $user->hasPermission('finance.reconciliations.pdf');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('finance.reconciliations.store');
    }

    public function update(User $user, ExpenseReconciliation $reconciliation): bool
    {
        return $user->hasPermission('finance.reconciliations.items.add')
            || $user->hasPermission('finance.reconciliations.items.remove')
            || $user->hasPermission('finance.reconciliations.close');
    }
}
