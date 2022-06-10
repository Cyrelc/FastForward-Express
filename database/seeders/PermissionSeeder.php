<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * @return void
     */
    public function run() {
        // Account Permissions
        $accountPermissions = [
            'accounts.create',
            'accounts.edit.*.*',
            'accounts.edit.basic.*',
            'accounts.edit.basic.children',
            'accounts.edit.basic.my',
            'accounts.edit.invoicing.children',
            'accounts.edit.invoicing.my',
            'accounts.view.*.*',
            'accounts.view.basic.*',
            'accounts.view.activityLog.children',
            'accounts.view.activityLog.my',
            'accounts.view.basic.my',
            'accounts.view.basic.children',
            'accounts.view.invoicing.my',
        ];

        $accountUserPermissions = [
            'accountUsers.create.*.*',
            'accountUsers.create.children',
            'accountUsers.create.my',
            'accountUsers.delete.*.*',
            'accountUsers.edit.*.*',
            'accountUsers.edit.basic.children',
            'accountUsers.edit.basic.my',
            'accountUsers.edit.permissions.children',
            'accountUsers.edit.permissions.my',
            'accountUsers.impersonate.*',
            'accountUsers.view.permissions.children',
            'accountUsers.view.permissions.my',
            'accountUsers.view.activityLog.my',
            'accountUsers.view.activityLog.children'
        ];

        $appSettings = [
            'appSettings.edit.*.*'
        ];

        $billPermissions = [
            'bills.create.*.*',
            'bills.create.basic.*',
            'bills.create.basic.children',
            'bills.create.basic.my',
            'bills.delete',
            'bills.edit.basic.*',
            'bills.edit.dispatch.*',
            'bills.edit.billing.*',
            'bills.view.basic.my',
            'bills.view.basic.children',
            'bills.view.basic.*',
            'bills.view.dispatch.*',
            'bills.view.billing.*',
            'bills.view.activityLog.*'
        ];

        $chargebackPermissions = [
            'chargebacks.view.*.*',
            'chargebacks.edit.*.*'
        ];

        $employeePermissions = [
            'employees.create',
            'employees.view.*.*',
            'employees.view.basic.*',
            'employees.edit.basic.*',
            'employees.edit.*.*'
        ];

        $interlinerPermissions = [
            'interliners.create.*.*',
            'interliners.edit.*.*'
        ];

        $invoicePermissions = [
            'invoices.create',
            'invoices.delete',
            'invoices.edit.*.*',
            'invoices.view.*.*',
            'invoices.view.children',
            'invoices.view.my',
        ];

        $manifestPermissions = [
            'manifests.create.*.*',
            'manifests.delete',
            'manifests.edit.*.*',
            'manifests.view.*.*'
        ];

        $paymentPermissions = [
            'payments.create.*.*',
            'payments.edit.*',
            'payments.view.*.*',
            'payments.view.children',
            'payments.view.my'
        ];

        $ratesheetPermissions = [
            'ratesheets.create.*.*',
            'ratesheets.edit.*.*',
            'ratesheets.view.*.*'
        ];

        /**
         * Other
         */
        $otherPermissions = ['appSettings.edit.*.*'];

        $permissions = array_merge(
            $accountPermissions,
            $accountUserPermissions,
            $billPermissions,
            $chargebackPermissions,
            $employeePermissions,
            $interlinerPermissions,
            $invoicePermissions,
            $manifestPermissions,
            $otherPermissions,
            $paymentPermissions,
            $ratesheetPermissions
        );

        foreach($permissions as $permission)
            if(!Permission::where('name', $permission)->first())
                Permission::create(['name' => $permission]);
    }
}
