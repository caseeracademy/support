<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['admin']);
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['admin']);
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['admin']);
    }
}
