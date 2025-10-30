<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->hasRole(['admin']);
    }

    public function restore(User $user, Transaction $transaction): bool
    {
        return $user->hasRole(['admin']);
    }

    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return $user->hasRole(['admin']);
    }
}
