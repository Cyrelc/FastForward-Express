<?php

namespace App\Http\Models\Account;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Account;
use App\Http\Models\User;

class AccountModelFactory {

    public function ListAll() {
        $model = new AccountsModel();

        $accountRepo = new Repos\AccountRepo();

        return $accountRepo->listAll();
    }

    public function GetCreateModel() {
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

        $model->account->invoice_sort_order = array();
        $allSortOptions = $invoiceRepo->GetSortOptions();
        foreach($allSortOptions as $sortOption) {
            $model->account->invoice_sort_order = array_merge($model->account->invoice_sort_order, [$sortOption]);
        }

        $model->invoice_intervals = $selectionsRepo->GetSelectionsByType('invoice_interval');

        return $model;
    }

    public function GetEditModel($accountId) {
        $model = new AccountFormModel();

        $accountRepo = new Repos\AccountRepo();
        $activityLogRepo = new Repos\ActivityLogRepo();
        $addressRepo = new Repos\AddressRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();
        $userModelFactory = new User\UserModelFactory();

        $contactsModelFactory = new Models\Partials\ContactsModelFactory();

        $model->account = $accountRepo->GetById($accountId);
        $model->account->invoice_sort_order = json_decode($model->account->invoice_sort_order);
        $model->invoice_intervals = $selectionsRepo->GetSelectionsByType('invoice_interval');
        $model->shipping_address = $addressRepo->GetById($model->account->shipping_address_id);
        $model->billing_address = $addressRepo->GetById($model->account->billing_address_id);
        $model->ratesheets = $ratesheetRepo->GetRatesheetSelectList();
        $model->child_account_list = $accountRepo->CountChildAccounts($accountId);

        if (isset($model->account->parent_account_id))
            $model->parentAccount = $accountRepo->GetById($model->account->parent_account_id);

        $model->parent_accounts = $accountRepo->GetParentAccountsList();
        $model->balance_owing = $invoiceRepo->CalculateAccountBalanceOwing($accountId);

        $allSortOptions = $invoiceRepo->GetSortOptions();
        foreach($allSortOptions as $sortOption) {
            foreach($model->account->invoice_sort_order as $invoiceSortOrder)
                if($sortOption->database_field_name === $invoiceSortOrder->database_field_name)
                    continue 2;
            $sortOption->priority = count($model->account->invoice_sort_order);
            $model->account->invoice_sort_order = array_merge($model->account->invoice_sort_order, [$sortOption]);
        }

        $model->activity_log = $activityLogRepo->GetAccountActivityLog($model->account->account_id);
        foreach($model->activity_log as $key => $log)
            $model->activity_log[$key]->properties = json_decode($log->properties);

        // $model->prev_id = $accountRepo->GetPrevActiveById($accountId);
        // $model->next_id = $accountRepo->GetNextActiveById($accountId);

        return $model;
    }
}

?>
