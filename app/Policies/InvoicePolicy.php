<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Http\Repos;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user) {
        return $user->hasAnyPermission('invoices.view.*.*') ||
            $user->accountUsers && $user->hasAnyPermission('invoices.view.my', 'invoices.view.children');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return mixed
     */
    public function view(User $user, Invoice $invoice) {
        if($user->can('invoices.view.*', 'invoices.edit.*'))
            return true;
        else if($user->accountUsers && $user->hasAnyPermission('invoices.view.my', 'invoices.view.children') && $invoice->finalized) {
            $accountRepo = new Repos\AccountRepo();
            return in_array($invoice->account_id, $accountRepo->GetMyAccountIds($user, $user->can('invoices.view.children')));
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
        return $user->can('invoices.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return mixed
     */
    public function update(User $user, Invoice $invoice) {
        return $user->can('invoices.edit.*.*');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return mixed
     */
    public function delete(User $user) {
        return $user->can('invoices.delete');
    }
}
