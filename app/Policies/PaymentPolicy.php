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
        if($payment->stripe_payment_intent_id == null) {
            return $user->can('payments.delete.*.*');
        } else {
            if($payment->stripe_object_type == 'refund')
                return false;
            if($payment->stripe_object_type == 'payment_intent') {
                $refund = Payment::where('stripe_payment_intent_id', $payment->stripe_payment_intent_id)
                    ->whereNotNull('stripe_refund_id')
                    ->first();
                return $refund == null;
            }
        }
        return false;
    }

    public function revertAny(User $user) {
        return $user->can('payments.delete.*.*');
    }
}
