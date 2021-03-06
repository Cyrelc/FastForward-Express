<?php

namespace App\Http\Models\Account;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Account;
use App\Http\Models\Permission;
use App\Http\Models\User;

class AccountModelFactory {
    public function GetCreateModel($permissions) {
        $model = new AccountFormModel();
        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();
        $contactModelFactory = new \App\Http\Models\Partials\ContactModelFactory();

        $model->parent_accounts = $accountRepo->GetParentAccountsList();
        $model->contact = $contactModelFactory->GetCreateModel();
        $model->account = new \App\Account();
        $model->delivery_address = new \App\Address();
        $model->billing_address = new \App\Address();
        $model->account->start_date = date("U");
        $model->commissions = [];
        $model->give_commission_1 = false;
        $model->give_commission_2 = false;
        $model->account->send_bills = 1;
        $model->account->send_invoices = 1;
        $model->ratesheets = $ratesheetRepo->GetRatesheetSelectList();
        $model->permissions = $permissions;

        $model->account->invoice_sort_order = $this->composeInvoiceSortOptions();

        $model->invoice_intervals = $selectionsRepo->GetSelectionsByType('invoice_interval');

        return $model;
    }

    public function GetEditModel($accountId, $permissions) {
        $model = new AccountFormModel();

        //Model factories
        $contactsModelFactory = new Models\Partials\ContactsModelFactory();
        $permissionModelFactory = new Permission\PermissionModelFactory();
        $userModelFactory = new User\UserModelFactory();

        //Repos
        $accountRepo = new Repos\AccountRepo();
        $activityLogRepo = new Repos\ActivityLogRepo();
        $addressRepo = new Repos\AddressRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $model->permissions = $permissions;
        $model->account = $accountRepo->GetByIdWithPermissions($accountId, $permissions);

        $model->account->invoice_sort_order = json_decode($model->account->invoice_sort_order);
        $model->billing_address = $addressRepo->GetById($model->account->billing_address_id);
        $model->invoice_intervals = $selectionsRepo->GetSelectionsByType('invoice_interval');
        $model->shipping_address = $addressRepo->GetById($model->account->shipping_address_id);
        $model->account->invoice_sort_order = $this->composeInvoiceSortOptions($model->account->invoice_sort_order);

        if($permissions['viewChildren'])
            $model->child_account_list = $accountRepo->GetChildAccountList($accountId);

        if($permissions['viewParent'])
            $model->parent_accounts = $accountRepo->GetParentAccountsList($model->account->parent_account_id);

        if($permissions['editAdvanced']) {
            $model->ratesheets = $ratesheetRepo->GetRatesheetSelectList();
            $model->parent_accounts = $accountRepo->GetParentAccountsList();
        }

        if($permissions['viewPayments'] || $permissions['editPayments'])
            $model->balance_owing = $invoiceRepo->CalculateAccountBalanceOwing($accountId);

        if($model->permissions['viewActivityLog']) {
            $model->activity_log = $activityLogRepo->GetAccountActivityLog($model->account->account_id);
            foreach($model->activity_log as $key => $log)
                $model->activity_log[$key]->properties = json_decode($log->properties);
        }

        // $model->prev_id = $accountRepo->GetPrevActiveById($accountId);
        // $model->next_id = $accountRepo->GetNextActiveById($accountId);

        return $model;
    }

    private function composeInvoiceSortOptions($accountInvoiceSortOptions = []) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $allSortOptions = $invoiceRepo->GetSortOptions();

        /**
         * First we iterate through the existing list, and remove any sort options we already have entries for
         */
        foreach($accountInvoiceSortOptions as $accountInvoiceSortOption)
            foreach($allSortOptions as $key => $sortOption)
                if($sortOption->invoice_sort_option_id == $accountInvoiceSortOption->invoice_sort_option_id)
                    unset($allSortOptions[$key]);

        /**
         * Then we can add the "missing" (or new) ones
         */
        $counter = count($accountInvoiceSortOptions);
        foreach($allSortOptions as $sortOption) {
            $sortOption['priority'] = $counter;
            array_push($accountInvoiceSortOptions, $sortOption);
            $counter ++;
        }

        foreach($accountInvoiceSortOptions as $accountInvoiceSortOption)
            if($accountInvoiceSortOption->can_be_subtotaled) {
                if(!isset($accountInvoiceSortOption->group_by))
                    $accountInvoiceSortOption->group_by = false;
            } else
                $accountInvoiceSortOption->group_by = null;

        return $accountInvoiceSortOptions;
    }
}

?>
