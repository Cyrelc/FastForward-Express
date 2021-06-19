<?php

namespace App\Policies;

use App\Bill;
use App\User;
use App\Http\Repos;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user) {
        return $user->hasAnyPermission('bills.view.*.*', 'bills.view.basic.*', 'bills.edit.*.*', 'bills.edit.basic.*', 'bills.view.basic.my', 'bills.view.basic.children') ||
            $user->employee && $user->employee->is_driver;
    }

    public function viewAll(User $user) {
        return $user->hasAnyPermission('bills.view.*.*', 'bills.view.basic.*', 'bills.view.dispatch.*', 'bills.view.billing.*', 'bills.edit.*.*', 'bills.edit.basic.*', 'bills.edit.dispatch.*', 'bills.edit.billing.*');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\Bill  $bill
     * @return mixed
     */
    public function viewBasic(User $user, Bill $bill) {
        if($user->hasAnyPermission('bills.view.*.*', 'bills.view.basic.*', 'bills.edit.*.*', 'bills.edit.basic.*'))
            return true;
        else if($user->employee && ($user->employee->employee_id === $bill->pickup_driver_id || $user->employee->employee_id === $bill->delivery_driver_id))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('bills.view.basic.my', 'bills.view.basic.children')) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($bill->charge_account_id, $accountRepo->GetMyAccountIds($user, $user->can('bills.view.basic.children')));
        }
        return false;
    }

    public function viewDispatch(User $user) {
        return $user->hasAnyPermission('bills.view.*.*', 'bills.edit.*.*', 'bills.view.dispatch.*', 'bills.edit.dispatch.*');
    }

    public function viewBilling(User $user) {
        return $user->hasAnyPermission('bills.view.billing.*', 'bills.edit.billing.*');
    }

    public function viewActivityLog(User $user, Bill $bill) {
        return $user->hasAnyPermission('bills.view.*.*', 'bills.edit.*.*', 'bills.view.activityLog.*');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function createBasic(User $user) {
        return $user->hasAnyPermission('bills.create.basic.my', 'bills.create.basic.children', 'bills.create.basic.*', 'bills.create.*.*');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function createFull(User $user) {
        return $user->can('bills.create.*.*');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\Bill  $bill
     * @return mixed
     */
    public function updateBasic(User $user, Bill $bill) {
        return $user->hasAnyPermission('bills.edit.basic.*') ||
            $user->employee && $user->can('bills.edit.basic.my') && ($user->employee->employee_id === $bill->pickup_employee_id || $user->employee->employee_id === $bill->delivery_employee_id);
    }

    public function updateDispatch(User $user, Bill $bill) {
        return $user->hasAnyPermission('bills.edit.dispatch.*');
    }

    public function updateBilling(User $user, Bill $bill) {
        return $user->hasAnyPermission('bills.edit.billing.*');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Bill  $bill
     * @return mixed
     */
    public function delete(User $user, Bill $bill)
    {
        return $user->can('bills.delete');
    }
}
