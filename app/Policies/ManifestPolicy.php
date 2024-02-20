<?php

namespace App\Policies;

use App\Models\Manifest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManifestPolicy
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
        //Users who have edit any, edit specific, or view specific should be able to see the list
        return $user->hasAnyPermission('manifests.view.*', 'manifests.edit.*') ||
            $user->employee && $user->employee->is_driver;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manifest  $manifest
     * @return mixed
     */
    public function view(User $user, Manifest $manifest)
    {
        return $user->hasAnyPermission('manifests.view.*', 'manifests.edit.*') ||
            $user->employee && $user->employee->is_driver && $user->employee->employee_id == $manifest->employee_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('manifests.create.*.*');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manifest  $manifest
     * @return mixed
     */
    public function update(User $user, Manifest $manifest)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manifest  $manifest
     * @return mixed
     */
    public function delete(User $user, Manifest $manifest)
    {
        return $user->can('manifests.delete');
    }
}
