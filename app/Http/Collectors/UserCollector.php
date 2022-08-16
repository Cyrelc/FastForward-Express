<?php

namespace App\Http\Collectors;

use App\Http\Repos;

class UserCollector {
    public function CollectUserForEmployee($req) {
        $userId = null;
        foreach($req->emails as $email)
        if(filter_var($email['is_primary'], FILTER_VALIDATE_BOOLEAN)) {
            $primaryEmail = $email['email'];
            break;
        }

        if(isset($req->employee_id) && $req->employee_id !== '') {
            $employeeRepo = new Repos\EmployeeRepo();
            $userId = $employeeRepo->GetById($req->employee_id, null)->user_id;
        }

        $user = [
            // 'username' => substr($req->first_name, 0, 1) . $req->last_name,
            'is_enabled' => filter_var($req->is_enabled, FILTER_VALIDATE_BOOLEAN),
            'email' => $primaryEmail,
            'user_id' => $userId
        ];

        return $user;
    }

    public function CollectAccountUser($req, $contactId, $primaryEmail, $userId = null) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($req->account_id);

        $accountUser = [
            'account_id' => $req->account_id,
            'contact_id' => $contactId,
            'user_id' => $userId,
            'email' => $primaryEmail
        ];

        if($req->user()->can('updateAccountUserPermissions', $account))
            $accountUser = array_merge($accountUser, ['is_enabled' => filter_var($req->permissions['is_enabled'], FILTER_VALIDATE_BOOLEAN)]);

        return $accountUser;
    }

    public function CollectAccountUserPermissions($req) {
        $permissionMap = [
            /* Accounts */
            'accounts.edit.basic.my' => 'editAccountBasicMy',
            'accounts.edit.basic.children' => 'editAccountBasicChildren',
            'accounts.edit.invoicing.my' => 'editAccountInvoiceSettingsMy',
            'accounts.edit.invoicing.children' => 'editAccountInvoiceSettingsChildren',
            'accounts.view.basic.children' => 'viewAccountsBasicChildren',
            'accounts.view.activityLog.my' => 'viewAccountActivityLogMy',
            'accounts.view.activityLog.children' => 'viewAccountActivityLogChildren',
            /* Account Users */
            'accountUsers.create.my' => 'createAccountUsersMy',
            'accountUsers.create.children' => 'createAccountUsersChildren',
            'accountUsers.edit.basic.my' => 'editAccountUsersMy',
            'accountUsers.edit.basic.children' => 'editAccountUsersChildren',
            'accountUsers.edit.permissions.my' => 'editAccountUserPermissionsMy',
            'accountUsers.edit.permissions.children' => 'editAccountUserPermissionsChildren',
            'accountUsers.view.activityLog.children' => 'viewAccountUserActivityLogsChildren',
            'accountUsers.view.activityLog.my' => 'viewAccountUserActivityLogsMy',
            'accountUsers.view.permissions.children' => 'viewAccountUserPermissionsChildren',
            'accountUsers.view.permissions.my' => 'viewAccountUserPermissionsMy',
            /* Bills */
            'bills.create.basic.my' => 'createBillsMy',
            'bills.create.basic.children' => 'createBillsChildren',
            'bills.view.basic.my' => 'viewBillsMy',
            'bills.view.basic.children' => 'viewBillsChildren',
            /* Invoices */
            'invoices.view.my' => 'viewInvoicesMy',
            'invoices.view.children' => 'viewInvoicesChildren',
            /* Payments */
            'payments.edit.children' => 'editPaymentsChildren',
            'payments.edit.my' => 'editPaymentsMy',
            'payments.view.children' => 'viewPaymentsChildren',
            'payments.view.my' => 'viewPaymentsMy',
        ];

        $permissions = [];
        foreach($permissionMap as $key => $value)
            $permissions[$key] = filter_var($req->permissions[$value], FILTER_VALIDATE_BOOLEAN);

        return $permissions;
    }

    public function CollectEmployeePermissions($req) {
        $permissionMap = [
            /* Accounts */
            'accounts.create' => 'createAccounts',
            'accounts.edit.basic.*' => 'editAccountsBasic',
            'accounts.edit.*.*' => 'editAccountsFull',
            'accounts.view.basic.*' => 'viewAccountsBasic',
            'accounts.view.*.*' => 'viewAccountsFull',
            /* Account Users */
            'accountUsers.create.*.*' => 'createAccountUsers',
            'accountUsers.delete.*.*' => 'deleteAccountUsers',
            'accountUsers.edit.*.*' => 'editAccountUsers',
            'accountUsers.impersonate.*' => 'impersonateAccountUsers',
            /* App Settings */
            'appSettings.edit.*.*' => 'editAppSettings',
            /* Bills */
            'bills.create.basic.*' => 'createBillsBasic',
            'bills.create.*.*' => 'createBillsFull',
            'bills.delete' => 'deleteBills',
            'bills.view.basic.*' => 'viewBillsBasic',
            'bills.view.dispatch.*' => 'viewBillsDispatch',
            'bills.view.billing.*' => 'viewBillsBilling',
            'bills.view.activityLog.*' => 'viewBillsActivityLog',
            'bills.edit.basic.*' => 'editBillsBasic',
            'bills.edit.dispatch.*' => 'editBillsDispatch',
            'bills.edit.billing.*' => 'editBillsBilling',
            /* Chargebacks */
            'chargebacks.view.*.*' => 'viewChargebacks',
            'chargebacks.edit.*.*' => 'editChargebacks',
            /* Employees */
            'employees.create' => 'createEmployees',
            'employees.view.basic.*' => 'viewEmployeesBasic',
            'employees.view.*.*' => 'viewEmployeesAdvanced',
            'employees.edit.basic.*' => 'editEmployeesBasic',
            'employees.edit.*.*' => 'editEmployeesAdvanced',
            /* Invoices */
            'invoices.create' => 'createInvoices',
            'invoices.view.*.*' => 'viewInvoices',
            'invoices.edit.*.*' => 'editInvoices',
            'invoices.delete' => 'deleteInvoices',
            /* Manifests */
            'manifests.create.*.*' => 'createManifests',
            'manifests.delete' => 'deleteManifests',
            'manifests.edit.*.*' => 'editManifests',
            'manifests.view.*.*' => 'viewManifests',
            /* Payments */
            'payments.create.*.*' => 'createPayments',
            'payments.edit.*' => 'editPayments',
            'payments.delete.*.*' => 'undoPayments',
            'payments.view.*.*' => 'viewPayments',
            /* Ratesheets */
            'ratesheets.create.*.*' => 'editAppSettings',
            'ratesheets.edit.*.*' => 'editAppSettings',
            'ratesheets.view.*.*' => 'editAppSettings'
        ];

        $permissions = [];
        foreach($permissionMap as $key => $value)
            $permissions[$key] = filter_var($req->permissions[$value], FILTER_VALIDATE_BOOLEAN);

        return $permissions;
    }
}
