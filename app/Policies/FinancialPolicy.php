<?php

namespace App\Policies;

use App\Models\FinancialAccess;
use App\Models\User;

class FinancialPolicy
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
        return $this->hasFinancialPermission($user);
    }

    public function reconcile(User $user, ?FinancialAccess $context = null): bool
    {
        return $user->hasPermission('reconciliation.pending')
            || $user->hasPermission('reconciliation.match')
            || $user->hasPermission('reconciliation.bulk-match')
            || $user->hasPermission('reconciliation.bulk-create-and-match')
            || $this->hasFinancialPermission($user);
    }

    public function manage(User $user, ?FinancialAccess $context = null): bool
    {
        return $this->hasFinancialPermission($user);
    }

    private function hasFinancialPermission(User $user): bool
    {
        $financialPermissions = [
            'transactions.index',
            'transactions.store',
            'transactions.confirm',
            'finance.reconciliations.index',
            'finance.reconciliations.store',
            'finance.reconciliations.close',
            'finance.reconciliations.pdf',
            'societies.financial.index',
            'societies.financial.movements',
            'societies.financial.movements.confirm',
            'societies.obligations.index',
            'societies.obligations.store',
            'societies.obligations.pay',
        ];

        foreach ($financialPermissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
