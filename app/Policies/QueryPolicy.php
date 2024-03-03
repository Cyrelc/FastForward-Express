<?php

namespace App\Policies;

use App\Models\Query;
use App\Models\User;
use App\Http\Repos;
use Illuminate\Auth\Access\HandlesAuthorization;

class QueryPolicy
{
    use HandlesAuthorization;

    public function delete(User $user, Query $query) {
        return $user->id == $query->user_id;
    }
}
