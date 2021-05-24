<?php
namespace App\Http\Models\Permission;

use App\Http\Repos;
use Illuminate\Support\Facades\Auth;

use App\Account;
use App\User;

class PermissionModelFactory {
    /**
     * There are two types of permissions - ones belonging to the authenticated user, and ones belonging to the model
     * i.e. You may have the ability to edit a user (a permission you have), but the user being edited may not have that permission (permissions owned by the model)
     * Since we have to make a distinction on certain objects, such as accountUsers and Employees, permissions owned by the model will contain "Model" in their function and object parameter names
     */
    public function getAccountPermissions($user, $account) {
        $permissions = [
            'create' => $user->can('create', Account::class),
            'editAdvanced' => $user->can('accounts.edit.advanced.*'),
            'editInvoicing' => $user->can('accounts.edit.invoicing.*'),
            'editBasic' => $user->can('accounts.edit.basic.*')
        ];

        if($account)
            $permissions = array_merge($permissions, [
                'createAccountUsers' => $user->can('createAccountUsers', $account),
                'createPayments' => $user->can('createPayments', $account),
    
                'deleteAccountUsers' => $user->can('delete', AccountUser::class),
    
                'editAdvanced' => $user->can('updateAdvanced', $account),
                'editBasic' => $user->can('updateBasic', $account),
                'editInvoicing' => $user->can('updateInvoicing', $account),
                'editPayments' => $user->can('editPayments', $account),
                'editAccountUsersBasic' => $user->can('updateAccountUsersBasic', $account),
                'editAccountUserPermissions' => $user->can('updateAccountUserPermissions', $account),
    
                'viewActivityLog' => $user->can('viewActivityLog', $account),
                'viewBills' => $user->can('viewBills', $account),
                'viewChildren' => $user->can('viewChildAccounts', $account),
                'viewInvoices' => $user->can('viewInvoices', $account),
                'viewParent' => $account->parent_account_id ? $user->can('view', Account::where('account_id', $account->parent_account_id)->first()) : false,
                'viewPayments' => $user->can('viewPayments', $account),
                'viewAccountUsers' => $user->can('viewAccountUsers', $account),
            ]);

        return $permissions;
    }

    public function GetBillPermissions($user, $bill = null) {
        $permissions = [
            'createBasic' => $user->can('createBasic', Bill::class),
            'createFull' => $user->can('createFull', Bill::class),
        ];
        if($bill)
            $permissions = array_merge($permissions, [
                'editBasic' => $user->can('updateBasic', $bill),
                'editDispatch' => $user->can('updateDispatch', $bill),
                'editBilling' => $user->can('updateBilling', $bill),
                'viewBasic' => $user-> can('viewBasic', $bill),
                'viewDispatch' => $user->can('viewDispatch', $bill),
                'viewBilling' => $user->can('viewBilling', $bill),
                'viewActivityLog' => $user->can('viewActivityLog', $bill)
            ]);
        return $permissions;
    }

    public function GetAccountUserPermissions($user, $accountUser, $account = null) {
        $permissions = [];

        if($account)
            $permissions = array_merge($permissions, [
                'create' => $user->can('createAccountUsers', $account),
                'editPermissions' => $user->can('updateAccountUserPermissions', $account)
            ]);

        if($accountUser)
            $permissions = array_merge($permissions, [
                'viewPermissions' => $user->can('viewPermissions', $accountUser),
                'viewActivityLog' => $user->can('viewActivityLog', $accountUser),
                'editPermissions' => $user->can('updatePermissions', $accountUser),
                'editBasic' => $user->can('updateBasic', $accountUser)
            ]);

        return $permissions;
    }

    public function GetAccountUserModelPermissions($accountUser) {
        if($accountUser)
            $user = User::where('user_id', $accountUser->user_id)->first();

        return [
            'is_enabled' => $accountUser ? $user->is_enabled : true,
            //account
            'viewAccountActivityLogMy' => $accountUser ? $user->can('accounts.view.activityLog.my') : false,
            'viewAccountActivityLogChildren' => $accountUser ? $user->can('accounts.view.activityLog.children') : false,
            'viewAccountsBasicChildren' => $accountUser ? $user->can('accounts.view.basic.children') : false,
            'editAccountBasicMy' => $accountUser ? $user->can('accounts.edit.basic.my') : false,
            'editAccountBasicChildren' => $accountUser ? $user->can('accounts.edit.basic.children') : false,
            'editAccountInvoiceSettingsMy' => $accountUser ? $user->can('accounts.edit.invoicing.my') : false,
            'editAccountInvoiceSettingsChildren' => $accountUser ? $user->can('accounts.edit.invoicing.children') : false,
            //accountUsers
            'createAccountUsersMy' => $accountUser ? $user->can('accountUsers.create.my') : false,
            'createAccountUsersChildren' => $accountUser ? $user->can('accountUsers.create.children') : false,
            'editAccountUsersMy' => $accountUser ? $user->can('accountUsers.edit.basic.my') : false,
            'editAccountUsersChildren' => $accountUser ? $user->can('accountUsers.edit.basic.children') : false,
            'editAccountUserPermissionsMy' => $accountUser ? $user->can('accountUsers.edit.permissions.my') : false,
            'editAccountUserPermissionsChildren' => $accountUser ? $user->can('accountUsers.edit.permissions.children') : false,
            'viewAccountUserPermissionsMy' => $accountUser ? $user->can('accountUsers.view.permissions.my') : false,
            'viewAccountUserPermissionsChildren' => $accountUser ? $user->can('accountUsers.view.permissions.children') : false,
            'viewAccountUserActivityLogsMy' => $accountUser ? $user->can('accountUsers.view.activityLog.my') : false,
            'viewAccountUserActivityLogsChildren' => $accountUser ? $user->can('accountUsers.view.activityLog.children') : false,
            //bills
            'createBillsMy' => $accountUser ? $user->can('bills.create.basic.my') : false,
            'createBillsChildren' => $accountUser ? $user->can('bills.create.basic.children') : false,
            'viewBillsMy' => $accountUser ? $user->can('bills.view.basic.my') : false,
            'viewBillsChildren' => $accountUser ? $user->can('bills.view.basic.children') : false,
            //invoicing
            'viewInvoicesMy' => $accountUser ? $user->can('invoices.view.my') : false,
            'viewInvoicesChildren' => $accountUser ? $user->can('invoices.view.children') : false,
            //payments
            'viewPaymentsMy' => $accountUser ? $user->can('payments.view.my') : false,
            'viewPaymentsChildren' => $accountUser ? $user->can('payments.view.children') : false
        ];
    }

    public function GetEmployeePermissions($user, $employee = null) {
        $permissions = [
            'create' => $user->can('create', Employee::class),
            'editAdvanced' => $user->can('employees.edit.*.*'),
            'viewAdvanced' => $user->can('employees.view.*.*')
        ];

        if($employee)
            $permissions = array_merge($permissions, [
                'viewBasic' => $user->can('view', $employee),
                'viewAdvanced' => $user->can('viewAdvanced', $employee),
                'editBasic' => $user->can('updateBasic', $employee),
                'editAdvanced' => $user->can('updateAdvanced', $employee),
                'viewActivityLog' => $user->can('viewActivityLog', $employee)
            ]);

        return $permissions;
    }

    public function GetEmployeeModelPermissions($employeeId) {
        if($employeeId) {
            $userRepo = new Repos\UserRepo();
            $user = $userRepo->GetUserByEmployeeId($employeeId);
        } else 
            $user = false;

        return [
            /* Accounts */
            'createAccounts' => $user ? $user->can('accounts.create') : false,
            'editAccountsBasic' => $user ? $user->can('accounts.edit.basic.*') : false,
            'editAccountsFull' => $user ? $user->can('accounts.edit.*.*') : false,
            'viewAccountsBasic' => $user ? $user->can('accounts.view.basic.*') : false,
            'viewAccountsFull' => $user ? $user->can('accounts.view.*.*') : false,
            /* Account Users */
            'createAccountUsers' => $user ? $user->can('accountUsers.create.*.*') : false,
            'deleteAccountUsers' => $user ? $user->can('accountUsers.delete.*.*') : false,
            'editAccountUsers' => $user ? $user->can('accountUsers.edit.*.*') : false,
            /* App settings */
            'editAppSettings' => $user ? $user->can('appSettings.edit.*.*') : false,
            /* Bills */
            'viewBillsBasic' => $user ? $user->can('bills.view.basic.*') : false,
            'createBillsBasic' => $user ? $user->can('bills.create.basic.*') : false,
            'createBillsFull' => $user ? $user->can('bills.create.*.*') : false,
            'deleteBills' => $user ? $user->can('bills.delete') : false,
            'viewBillsDispatch' => $user ? $user->can('bills.view.dispatch.*') : false,
            'viewBillsBilling' => $user ? $user->can('bills.view.billing.*') : false,
            'viewBillsActivityLog' => $user ? $user->can('bills.view.activityLog.*') : false,
            'editBillsBasic' => $user ? $user->can('bills.edit.basic.*') : false,
            'editBillsDispatch' => $user ? $user->can('bills.edit.dispatch.*') : false,
            'editBillsBilling' => $user ? $user->can('bills.edit.billing.*') : false,
            /* Chargebacks */
            'viewChargebacks' => $user ? $user->can('chargebacks.view.*.*') : false,
            'editChargebacks' => $user ? $user->can('chargebacks.edit.*.*') : false,
            /* Employees */
            'createEmployees' => $user ? $user->can('employees.create') : false,
            'editEmployeesBasic' => $user ? $user->can('employees.edit.basic.*') : false,
            'editEmployeesAdvanced' => $user ? $user->can('employees.edit.*.*') : false,
            'viewEmployeesBasic' => $user ? $user->can('employees.view.basic.*') : false,
            'viewEmployeesAdvanced' => $user ? $user->can('employees.view.*.*') : false,
            /* Invoices */
            'viewInvoices' => $user ? $user->can('invoices.view.*.*') : false,
            'createInvoices' => $user ? $user->can('invoices.create') : false,
            'editInvoices' => $user ? $user->can('invoices.update.*.*') : false,
            'deleteInvoices' => $user ? $user->can('invoices.delete') : false,
            /* Manifests */
            'createManifests' => $user ? $user->can('manifests.create') : false,
            'deleteManifests' => $user ? $user->can('manifests.delete') : false,
            'editManifests' => $user ? $user->can('manifests.edit.*.*') : false,
            'viewManifests' => $user ? $user->can('manifests.view.*.*') : false,
            /* Payments */
            'createPayments' => $user ? $user->can('payments.create.*.*') : false,
            'viewPayments' => $user ? $user->can('payments.view.*.*') : false,
            'editPayments' => $user ? $user->can('payments.edit.*') : false,
        ];
    }

    public function getFrontEndPermissionsForUser($user) {
        $model = new FrontEndPermissionsModel();

        $model->accounts = [
            'create' => $user->can('create', Account::class),
            'toggleEnabled' => $user->can('accounts.edit.*.*'),
            'viewAll' => $user->can('viewAll', Account::class),
            'viewAny' => $user->can('viewAny', Account::class),
        ];
        $model->appSettings = [
            'edit' => $user->can('appSettings.edit.*.*')
        ];
        $model->bills = [
            'create' => $user->can('createBasic', Bill::class),
            'dispatch' => $user->can('viewDispatch', Bill::class),
            'viewAny' => $user->can('viewAny', Bill::class),
            'delete' => $user->can('bills.delete')
        ];
        $model->chargebacks = [
            'viewAny' => $user->can('viewAny', Chargeback::class)
        ];
        $model->employees = [
            'create' => $user->can('create', Employee::class),
            'viewAny' => $user->can('viewAny', Employee::class),
            'viewAll' => $user->can('viewAll', Employee::class),
            'edit' => $user->can('employees.edit.*.*')
        ];
        $model->invoices = [
            'create' => $user->can('create', Invoice::class),
            'viewAny' => $user->can('viewAny', Invoice::class),
            'delete' => $user->can('delete', Invoice::class),
            'edit' => $user->can('invoices.edit.*.*')
        ];
        $model->manifests = [
            'create' => $user->can('create', Manifest::class),
            'viewAny' => $user->can('viewAny', Manifest::class),
            'delete' => $user->can('manifests.delete')
        ];

        return $model;
    }

    public function getInvoicePermissions($user, $invoice) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($invoice->account_id);

        return [
            'amend' => $user->can('edit', $invoice),
            'edit' => $user->can('edit', $invoice),
            'viewBills' => $user->can('viewBills', $account)
        ];
    }
}

?>
