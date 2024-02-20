<?php

namespace App\Policies;

use App\Models\Ratesheet;
use App\Models\User;
use App\Http\Repos;
use Illuminate\Auth\Access\HandlesAuthorization;

class RatesheetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user) {
        return $user->can('ratesheets.create.*.*');
    }

    /**
     * Determine whether the user can run charges against a given ratesheet
     */
    public function getChargesFrom(User $user, Ratesheet $ratesheet) {
        if($user->accountUsers) {
            $ratesheetRepo = new Repos\RatesheetRepo();
            $myRatesheets = $ratesheetRepo->GetForBillsPage();
            foreach($myRatesheets as $ratesheet)
                if($ratesheet->ratesheet_id === $ratesheet->ratesheet_id)
                    return true;
            return false;
        }
        return $user->hasAnyPermission('bills.create.*.*', 'bills.create.basic.*');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ratesheet  $ratesheet
     * @return mixed
     */
    public function update(User $user, Ratesheet $ratesheet) {
        return $user->can('ratesheets.edit.*.*');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ratesheet  $ratesheet
     * @return mixed
     */
    public function view(User $user, Ratesheet $ratesheet) {
        return $user->can('ratesheets.view.*.*');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user) {
        return $user->can('ratesheets.view.*.*');
    }
}
