<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'hr']);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasRole(['admin', 'hr']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'hr']);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasRole(['admin', 'hr']);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasRole(['admin']);
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $user->hasRole(['admin']);
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        return $user->hasRole(['admin']);
    }
}
