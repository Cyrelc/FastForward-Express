<?php

namespace App\Policies;

use App\Models\Payment;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user) {
        return $user->can('payments.create.*.*');
    }

    public function undo(User $user) {
        return $user->can('payments.delete.*.*');
    }
}
