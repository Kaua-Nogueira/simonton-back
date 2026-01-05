<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
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
        return $user->hasPermission('transactions.index');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->hasPermission('transactions.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('transactions.store');
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->hasPermission('transactions.update')
            && $transaction->status !== 'confirmed';
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->hasPermission('transactions.destroy');
    }

    /**
     * Exemplo de regra de negócio
     */
    public function confirm(User $user, Transaction $transaction): bool
    {
        return $user->hasPermission('transactions.confirm')
            && $transaction->status === 'pending';
    }

    public function split(User $user, Transaction $transaction): bool
    {
        return $user->hasPermission('transactions.split');
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('transactions.import');
    }
}
