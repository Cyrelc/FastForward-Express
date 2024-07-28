<?php

namespace App\Http\Models\Account;

use App\Http\Repos;
use App\Http\Models\Permission\PermissionModelFactory;
use App\Http\Models\User\UserModelFactory;
use App\Http\Resources\PaymentResource;
use App\Models\Account;
use App\Models\Address;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\User;
use App\Services\ContactService;

class AccountModelFactory {
    public function GetBillingModel($accountId) {
        $invoiceRepo = new Repos\InvoiceRepo();

        $model = new \stdClass();
        $model->payments = PaymentResource::collection(Payment::where('account_id', $accountId)->get());
        $model->outstanding_invoices = $invoiceRepo->GetOutstandingByAccountId($accountId);

        return $model;
    }

    public function GetCreateModel($permissions) {
        $model = new AccountFormModel();
        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $model->parent_accounts = $accountRepo->GetParentAccountsList();
        $model->account = new Account();
        $model->delivery_address = new Address();
        $model->billing_address = new Address();
        $model->account->start_date = date("U");
        $model->commissions = [];
        $model->give_commission_1 = false;
        $model->give_commission_2 = false;
        $model->account->send_bills = 1;
        $model->account->send_invoices = 1;
        $model->ratesheets = $ratesheetRepo->GetRatesheetSelectList();
        $model->permissions = $permissions;

        $model->account->invoice_sort_order = $accountRepo->GetInvoiceSortOrder();
        foreach($model->account->invoice_sort_order as $key => $sort_option) {
            $model->account->invoice_sort_order[$key]->subtotal_by = filter_var($sort_option->can_be_subtotaled, FILTER_VALIDATE_BOOLEAN) ? false : null;
        }

        $model->invoice_intervals = $selectionsRepo->GetSelectionsByType('invoice_interval');

        return $model;
    }

    public function GetEditModel($accountId, $permissions) {
        $model = new AccountFormModel();

        //Model factories
        $permissionModelFactory = new PermissionModelFactory();
        $userModelFactory = new UserModelFactory();

        //Repos
        $accountRepo = new Repos\AccountRepo();
        $activityLogRepo = new Repos\ActivityLogRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $model->permissions = $permissions;
        $model->account = $accountRepo->GetByIdWithPermissions($accountId, $permissions);

        $model->billing_address = $model->account->billing_address;
        $model->invoice_intervals = $selectionsRepo->GetSelectionsByType('invoice_interval');
        $model->shipping_address = $model->account->shipping_address;
        $model->account->invoice_sort_order = $accountRepo->GetInvoiceSortOrder($accountId);
        foreach($model->account->invoice_sort_order as $key => $sort_option) {
            $model->account->invoice_sort_order[$key]->subtotal_by = filter_var($sort_option->can_be_subtotaled, FILTER_VALIDATE_BOOLEAN) ? $sort_option->subtotal_by : null;
        }

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

        return $model;
    }
}

?>
