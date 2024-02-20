<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;
use App\Http\Repos;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillPolicy
{
    use HandlesAuthorization;

    public function copyBill(User $user, Bill $bill) {
        if($user->hasAnyPermission('bills.create.basic.*', 'bills.create.*.*'))
            return true;
        else if($user->accountUsers && $this->billBelongsToMyAccounts($user, $bill))
            return true;
        return false;
    }
    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function createBasic(User $user) {
        return $user->hasAnyPermission('bills.create.basic.my', 'bills.create.basic.children', 'bills.create.basic.*', 'bills.create.*.*');
    }

    public function createBasicAnyAccount(User $user) {
        return $user->hasAnyPermission('bills.create.basic.*', 'bills.create.*.*');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function createFull(User $user) {
        return $user->can('bills.create.*.*');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Bill  $bill
     * @return mixed
     */
    public function delete(User $user, Bill $bill)
    {
        return $user->can('bills.delete');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Bill  $bill
     * @return mixed
     */
    public function updateBasic(User $user, Bill $bill) {
        return $user->hasAnyPermission('bills.edit.basic.*') ||
            $user->employee && $user->can('bills.edit.basic.my') && ($user->employee->employee_id === $bill->pickup_employee_id || $user->employee->employee_id === $bill->delivery_employee_id);
    }

    public function updateDispatch(User $user, Bill $bill) {
        return $user->hasAnyPermission('bills.edit.dispatch.*');
    }

    public function updateDispatchMy(User $user, Bill $bill) {
        return $user->employee && ($bill->pickup_driver_id == $user->employee->employee_id || $bill->delivery_driver_id == $user->employee->employee_id);
    }

    public function updateBilling(User $user, Bill $bill) {
        return $user->hasAnyPermission('bills.edit.billing.*');
    }

    public function viewActivityLog(User $user, Bill $bill) {
        return $user->hasAnyPermission('bills.view.*.*', 'bills.edit.*.*', 'bills.view.activityLog.*');
    }

    public function viewAll(User $user) {
        return $user->hasAnyPermission('bills.view.*.*', 'bills.view.basic.*', 'bills.view.dispatch.*', 'bills.view.billing.*', 'bills.edit.*.*', 'bills.edit.basic.*', 'bills.edit.dispatch.*', 'bills.edit.billing.*');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user) {
        return $user->hasAnyPermission('bills.view.*.*', 'bills.view.basic.*', 'bills.edit.*.*', 'bills.edit.basic.*', 'bills.view.basic.my', 'bills.view.basic.children') ||
            $user->employee && $user->employee->is_driver;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Bill  $bill
     * @return mixed
     */
    public function viewBasic(User $user, Bill $bill) {
        if($user->hasAnyPermission('bills.view.*.*', 'bills.view.basic.*', 'bills.edit.*.*', 'bills.edit.basic.*'))
            return true;
        else if($user->employee && ($user->employee->employee_id === $bill->pickup_driver_id || $user->employee->employee_id === $bill->delivery_driver_id))
            return true;
        else if($user->accountUsers && $this->billBelongsToMyAccounts($user, $bill))
            return true;
        return false;
    }

    public function viewBilling(User $user) {
        return $user->hasAnyPermission('bills.view.billing.*', 'bills.edit.billing.*');
    }

    public function viewDispatch(User $user) {
        return $user->hasAnyPermission('bills.view.*.*', 'bills.edit.*.*', 'bills.view.dispatch.*', 'bills.edit.dispatch.*');
    }

    /**
     * Private functions
     */
    private function billBelongsToMyAccounts($user, $bill) {
        $accountRepo = new Repos\AccountRepo();
        $chargeRepo = new Repos\ChargeRepo();

        $charges = $bill->charges;
        $myAccountIds = $accountRepo->GetMyAccountIds($user, $user->can('bills.view.basic.children'));

        $chargeArray = array();

        foreach($charges as $charge)
            if($charge->charge_account_id && in_array($charge->charge_account_id, $myAccountIds))
                return true;

        return false;
    }
}
