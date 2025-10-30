<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->hasRole(['admin', 'accounting']);
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->hasRole(['admin']);
    }
}
