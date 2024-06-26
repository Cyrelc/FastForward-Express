<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy {
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user) {
        return $user->can('payments.create.*.*');
    }

    public function revert(User $user, Payment $payment) {
        return $user->can('payments.delete.*.*');
    }

    public function revertAny(User $user) {
        return $user->can('payments.delete.*.*');
    }
}
