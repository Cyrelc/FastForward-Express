<?php

namespace App\Policies;

use App\Models\Interliner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InterlinerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('interliners.view.*.*');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Interliner  $interliner
     * @return mixed
     */
    public function view(User $user, Interliner $interliner)
    {
        return $user->can('interliners.view.*.*');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('appSettings.edit.*.*');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Interliner  $interliner
     * @return mixed
     */
    public function update(User $user, Interliner $interliner)
    {
        return $user->can('appSettings.edit.*.*');
    }
}
