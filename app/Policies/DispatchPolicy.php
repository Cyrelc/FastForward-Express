<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DispatchPolicy
{
    use HandlesAuthorization;

    public function viewDispatch(User $user) {
        return $user->hasAnyPermission('bills.view.dispatch.*');
    }
}
