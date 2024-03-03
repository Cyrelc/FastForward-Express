<?php

namespace App\Models;

use App\Scopes\EmployeeScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use HasFactory, LogsActivity;

    public $timestamps = false;
    public $primaryKey = "employee_id";

    protected $fillable = [
        'employee_id',
        'contact_id',
        'company_name',
        'delivery_commission',
        'dob',
        'drivers_license_expiration_date',
        'drivers_license_number',
        'employee_number',
        'insurance_expiration_date',
        'insurance_number',
        'is_driver',
        'license_plate_expiration_date',
        'license_plate_number',
        'pickup_commission',
        'sin',
        'start_date',
        'updated_at',
        'user_id',
        'vehicle_type_id'
    ];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function activityLog() {
        return [];
    }

    public function contact() {
        return $this->hasOne(Contact::class, 'contact_id', 'contact_id');
    }

    public function emergency_contacts() {
        return $this->hasMany(EmployeeEmergencyContact::class, 'employee_id');
    }

    public function is_enabled() {
        return $this->user->is_enabled;
    }

    public function permissions() {
        $dbPermissions = $this->user ? $this->user->getPermissionNames()->toArray() : [];
        $permissions = array_flip(Employee::$permissionsMap);
        foreach($permissions as $friendly => $database) {
            $permissions[$friendly] = in_array($database, $dbPermissions);
        }

        return $permissions;
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function vehicleSelections() {
        return Selection::where('type', 'vehicle_type')->select('name as label', 'selection_id as value')->get();
    }

    public static $permissionsMap = [
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
        'payments.delete.*.*' => 'revertPayments',
        'payments.view.*.*' => 'viewPayments',
        /* Ratesheets */
        // 'ratesheets.create.*.*' => 'editAppSettings',
        // 'ratesheets.edit.*.*' => 'editAppSettings',
        // 'ratesheets.view.*.*' => 'editAppSettings'
    ];
}
