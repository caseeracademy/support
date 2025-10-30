<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'hr']);
    }

    public function view(User $user, Payroll $payroll): bool
    {
        return $user->hasRole(['admin', 'hr']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'hr']);
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $user->hasRole(['admin', 'hr']);
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->hasRole(['admin']);
    }

    public function restore(User $user, Payroll $payroll): bool
    {
        return $user->hasRole(['admin']);
    }

    public function forceDelete(User $user, Payroll $payroll): bool
    {
        return $user->hasRole(['admin']);
    }
}
