<?php

namespace App\Policies;

use App\Chargeback;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChargebackPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user) {
        return $user->can('chargebacks.edit.*.*') || $user->can('chargebacks.view.*.*');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\Chargeback  $chargeback
     * @return mixed
     */
    public function view(User $user, Chargeback $chargeback) {
        return $user->can('chargebacks.edit.*.*') || $user->can('chargebacks.view.*.*');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user) {
        return $user->can('chargebacks.edit.*.*');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\Chargeback  $chargeback
     * @return mixed
     */
    public function update(User $user, Chargeback $chargeback) {
        return $user->can('chargebacks.edit.*.*');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Chargeback  $chargeback
     * @return mixed
     */
    public function delete(User $user, Chargeback $chargeback) {
        return $user->can('chargebacks.edit.*.*');
    }
}
