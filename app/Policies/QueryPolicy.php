<?php

namespace App\Policies;

use App\Query;
use App\User;
use App\Http\Repos;
use Illuminate\Auth\Access\HandlesAuthorization;

class QueryPolicy
{
    use HandlesAuthorization;

    public function delete(User $user, Query $query) {
        return $user->user_id == $query->user_id;
    }
}
