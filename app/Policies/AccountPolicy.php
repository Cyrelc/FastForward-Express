<?php

namespace App\Policies;

use App\Models\Account;
use App\User;
use App\Http\Repos;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountPolicy
{
    use HandlesAuthorization;

    private static $permissionsWhichConveyViewChildren = ['accounts.view.basic.children', 'accounts.edit.basic.children', 'accounts.edit.invoicing.children', 'payments.view.children', 'accountUsers.create.children', 'accountUsers.edit.children', 'accountUsers.editPermissions.children'];
    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user) {
        return $user->can('accounts.create');
    }

    public function createAccountUsers(User $user, Account $account) {
        if($user->can('accountUsers.create.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.create.my', 'accountUsers.create.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->can('accountUsers.create.children')));
        } else
            return false;
    }

    public function delete(User $user) {
        return $user->can('accountUsers.delete.*.*');
    }

    public function impersonateAccountUsers(User $user) {
        return $user->can('accountUsers.impersonate.*');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @return mixed
    */
    public function viewAll(User $user) {
        return $user->hasAnyPermission('accounts.view.*.*', 'accounts.edit.*.*', 'accounts.view.basic.*', 'accounts.edit.basic.*');
    }

    public function viewAny(User $user) {
        return $user->hasAnyPermission('accounts.view.*.*', 'accounts.edit.*.*', 'accounts.view.basic.*', 'accounts.edit.basic.*') ||
            count($user->accountUsers) != 0;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\Account  $account
     * @return mixed
     */
    public function view(User $user, Account $account) {
        if($user->hasAnyPermission('accounts.view.*.*', 'accounts.edit.*.*', 'accounts.view.basic.*', 'accounts.edit.basic.*'))
            return true;
        else if($user->accountUsers) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->hasAnyPermission('accounts.view.basic.children', 'accounts.edit.basic.children', 'accounts.edit.invoicing.children', 'accounts.view.invoicing.children')));
        } else
            return false;
    }

    /**
     * Determine whether the user can view account Users assigned to the model.
     *
     * @param  \App\User  $user
     * @param  \App\Account  $account
     * @return mixed
     */
    public function viewAccountUsers(User $user, Account $account) {
        if($user->hasAnyPermission('accountUsers.view.*.*', 'accountUsers.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.view.basic.my', 'accountUsers.edit.basic.my', 'accountUsers.view.basic.children', 'accountUsers.edit.basic.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->hasAnyPermission('accountUsers.view.basic.children', 'accountUsers.edit.basic.children')));
        }
        return false;
    }

    /**
     * Determine whether the user can view the activity log for model.
     *
     * @param  \App\User  $user
     * @param  \App\Account  $account
     * @return mixed
     */
    public function viewActivityLog(User $user, Account $account) {
        if($user->hasAnyPermission('accounts.view.*.*', 'accounts.edit.*.*', 'accounts.view.activityLog.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accounts.view.activityLog.my', 'accounts.view.activityLog.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->can('accounts.view.activityLog.children')));
        }
        return false;
    }

    public function viewBills(User $user, Account $account) {
        if($user->hasAnyPermission('bills.view.basic.*', 'bills.view.dispatch.*', 'bills.view.billing.*', 'bills.edit.basic.*', 'bills.edit.dispatch.*', 'bills.edit.billing.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('bills.view.basic.my', 'bills.view.basic.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->can('bills.view.basic.children')));
        }
        return false;
    }

    public function viewChildAccounts(User $user, Account $account) {
        if($user->hasAnyPermission('accounts.view.*.*', 'accounts.view.basic.*', 'accounts.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission(self::$permissionsWhichConveyViewChildren)) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->hasAnyPermission(self::$permissionsWhichConveyViewChildren)));
        }
        return false;
    }

    public function viewInvoices(User $user, Account $account) {
        if($user->hasAnyPermission('invoices.view.*.*', 'invoices.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('invoices.view.my', 'invoices.view.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->can('invoices.view.children')));
        }
        return false;
    }

    /**
     * Determine whether the user can view the payments for the model
     * 
     * @param \App\User $user
     * @param \App\Account $account
     * @return mixed
     */
    public function viewPayments(User $user, Account $account) {
        if($user->hasAnyPermission('payments.view.*.*', 'payments.create.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('payments.view.my', 'payments.view.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->can('payments.view.children')));
        }
        return false;
    }

    public function updateAccountUsersBasic(User $user, Account $account) {
        if($user->can('accountUsers.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.edit.basic.my', 'accountUsers.edit.basic.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->can('accountUsers.edit.basic.children')));
        }
        return false;
    }

    public function updateAccountUserPermissions(User $user, Account $account) {
        if($user->hasAnyPermission('accountUsers.edit.*.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accountUsers.edit.permissions.my', 'accountUsers.edit.permissions.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->can('accountUsers.edit.permissions.children')));
        }
    }

    /**
     * Updates are handled in sections - as different users have different sections
     * Determine whether the user can update the "Advanced" section of the model
     * @param \App\User $user
     * @param \App\Account $account
     * return mixed
     */
    public function updateAdvanced(User $user, Account $account) {
        return $user->can('accounts.edit.*.*');
    }

    /**
     * Determine whether the user can update the "basic" section of the model
     * @param \App\User $user
     * @param \App\Account $account
     * return mixed
     */
    public function updateBasic(User $user, Account $account) {
        if($user->hasAnyPermission('accounts.edit.*.*', 'accounts.edit.basic.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accounts.edit.basic.my', 'accounts.edit.basic.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->can('accounts.edit.basic.children')));
        }
        return false;
    }

    /**
     * Determine whether the user can update the "invoicing" section of the model
     * @param \App\User $user
     * @param \App\Account $account
     * return mixed
     */
    public function updateInvoicing(User $user, Account $account) {
        if($user->hasAnyPermission('accounts.edit.*.*', 'accounts.edit.basic.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('accounts.edit.invoicing.my', 'accounts.edit.invoicing.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($account->account_id, $accountRepo->GetMyAccountIds($user, $user->can('accounts.edit.invoicing.children')));
        }
        return false;
    }

    public function updatePayments(User $user, Account $account) {
        return $user->hasAnyPermission('payments.edit.*');
    }


    /**
     * Determine whether a user can manage payment methods for their account
     * @param \App\User $user
     * @param \App\Account $account
     * @return mixed
     */
    public function updatePaymentMethods(User $user, Account $account) {
        if($user->hasAnyPermission('payments.create.*.*', 'payments.edit.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('payments.edit.my', 'payments.edit.children')) {
            $accountRepo = new Repos\AccountRepo();
            $myAccountIds = $accountRepo->GetMyAccountIds($user, $user->can('payments.edit.children'));
            if(in_array($account->account_id, $myAccountIds))
                return true;
        }
        return false;
    }

    /**
     * Determine whether the user can toggle the account as enabled/disabled
     */
    public function toggleEnabled(User $user, Account $account) {
        return $user->can('accounts.edit.*.*');
    }
}
