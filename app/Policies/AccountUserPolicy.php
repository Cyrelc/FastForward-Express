<?php

namespace App\Policies;

use App\Models\AccountUser;
use App\Models\User;
use App\Http\Repos;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountUserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountUser  $accountUser
     * @return mixed
     */
    public function view(User $user, AccountUser $accountUser)
    {
        if($user->hasAnyPermission('accountUsers.view.*', 'accountUsers.edit.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.view.basic.my', 'accountUsers.edit.basic.my', 'accountUsers.view.basic.children', 'accountUsers.edit.basic.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($accountUser->account_id, $accountRepo->GetMyAccountIds($user, $user->hasAnyPermission('accountUsers.view.basic.children', 'accountUsers.edit.basic.children')));
        } else if($user->accountUsers) {
            foreach($user->accountUsers as $testAgainstUser)
                if($testAgainstUser->contact_id === $accountUser->contact_id)
                    return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view permissions for the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountUser  $accountUser
     * @return mixed
     */
    public function viewPermissions(User $user, AccountUser $accountUser) {
        if($user->hasAnyPermission('accountUsers.view.*.*', 'accountUsers.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.view.permissions.my', 'accountUsers.edit.permissions.my', 'accountUsers.view.permissions.children', 'accountUsers.edit.permissions.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($accountUser->account_id, $accountRepo->GetMyAccountIds($user, $user->hasAnyPermission('accountUsers.view.permissions.children', 'accountUsers.edit.permissions.children')));
        }
        return false;
    }

    /**
     * Determine whether the user can edit basic fields for the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountUser  $accountUser
     * @return mixed
     */
    public function updateBasic(User $user, AccountUser $accountUser) {
        if($user->can('accountUsers.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.edit.basic.my', 'accountUsers.edit.permissions.my', 'accountUsers.edit.basic.children', 'accountUsers.edit.permissions.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($accountUser->account_id, $accountRepo->GetMyAccountIds($user, $user->hasAnyPermission('accountUsers.edit.basic.children', 'accountUsers.edit.permissions.children')));
        } else if($user->accountUsers) {
            foreach($user->accountUsers as $testAgainstUser)
                if($testAgainstUser->contact_id === $accountUser->contact_id)
                    return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view permissions for the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountUser  $accountUser
     * @return mixed
     */
    public function updatePermissions(User $user, AccountUser $accountUser) {
        if($user->can('accountUsers.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.edit.permissions.my', 'accountUsers.edit.permissions.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($accountUser->account_id, $accountRepo->GetMyAccountIds($user, $user->can('accountUsers.edit.permissions.children')));
        }
        return false;
    }

    public function linkAccountUser(User $user, AccountUser $accountUser) {
        if($user->hasAnyPermission('accountUsers.create.*.*', 'accountUsers.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.create.my', 'accountUsers.create.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($accountUser->account_id, $accountRepo->GetMyAccountIds($user, $user->can('accountUsers.create.children')));
        }
        return false;
    }

    /**
     * Determine whether the user can view activityLog for the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountUser  $accountUser
     * @return mixed
     */
    public function viewActivityLog(User $user, AccountUser $accountUser) {
        if($user->hasAnyPermission('accountUsers.view.*', 'accountUsers.edit.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.view.activityLog.my', 'accountUsers.view.activityLog.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($accountUser->account_id, $accountRepo->GetMyAccountIds($user, $user->can('accountUsers.view.activityLog.children')));
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user) {
        return $user->can('accountUsers.create.*.*') ||
            $user->hasAnyPermission('accountUsers.create.my', 'accountUsers.create.children');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccountUser  $accountUser
     * @return mixed
     */
    public function delete(User $user) {
        return $user->can('accountUsers.delete.*.*');
    }
}
