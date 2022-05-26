<?php

namespace App\Policies;

use App\User;
use App\Http\Repos;

use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function linkUser(User $user, User $targetUser) {
        if($user->hasAnyPermission('accountUsers.create.*.*', 'accountUsers.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.create.my', 'accountUsers.create.children')) {
            $accountRepo = new Repos\AccountRepo();
            $myAccounts = $accountRepo->GetMyAccountIds($user, $user->can('accountUsers.create.children'));
            foreach($targetUser->accountUsers as $targetAccountUser)
                if(in_array($targetAccountUser->account_id, $myAccounts))
                    return true;
        }
        return false;
    }

    /**
     * Determine whether the user can change password
     *
     * @param  \App\User  $user
     * @param  \App\User  $targetUser
     * @return mixed
     */
    public function updatePassword(User $user, User $targetUser) {
        if($user->user_id === $targetUser->user_id)
            return true;
        else if($targetUser->employee) {
            return $user->hasAnyPermission('employees.edit.*.*');
        } else if ($user->employee && $targetUser->accountUsers) {
            return $user->hasAnyPermission('accountUsers.edit.*.*');
        } else if($targetUser->accountUsers && $user->hasAnyPermission('accountUsers.edit.permissions.my', 'accountUsers.edit.permissions.children')) {
            $accountRepo = new Repos\AccountRepo();
            $myAccountIds = $accountRepo->GetMyAccountIds($user, $user->can('accountUsers.edit.permissions.children'));
            foreach($targetUser->accountUsers as $targetAccountUser)
                if(in_array($targetAccountUser->account_id, $myAccountIds))
                    return true;
        }
        return false;
    }
}
