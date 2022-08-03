<?php
namespace App\Http\Models\Home;

use App\Http\Repos;
use App\Http\Models;
use Illuminate\Support\Facades\Auth;

class HomeModelFactory {
    public function GetAppConfiguration($req) {
        $accountRepo = new Repos\AccountRepo();
        $contactRepo = new Repos\ContactRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $paymentRepo = new Repos\PaymentRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $permissionModelFactory = new Models\Permission\PermissionModelFactory;

        $model = new AppModel();

        $model->frontEndPermissions = $permissionModelFactory->getFrontEndPermissionsForUser($req->user());
        $model->authenticatedEmployee = $req->user()->employee;
        $model->authenticatedAccountUsers = $req->user()->accountUsers;
        $model->authenticatedUserId = $req->user()->user_id;
        $model->is_impersonating = $req->session()->has('original_user_id');
        $model->payment_types = $paymentRepo->GetPaymentTypesList();

        if($model->authenticatedEmployee) {
            $model->contact = $contactRepo->GetById($model->authenticatedEmployee->contact_id);
            if($req->user()->can('viewAll', Account::class) || $req->user()->can('bills.view.basic.*'))
                $model->accounts = $accountRepo->List(null);
            if($req->user()->can('viewAll', Account::class)) {
                $model->invoice_intervals = $selectionsRepo->GetSelectionsListByType('invoice_interval');
                $model->parent_accounts = $accountRepo->GetParentAccountsList();
            }
            if($req->user()->can('viewAll', Employee::class) || $req->user()->can('bills.view.dispatch.*')) {
                $model->employees = $employeeRepo->GetEmployeesList($req->user()->can('viewAll', Employee::class) ? null : $model->authenticatedEmployee->employee_id);
            }
            if($req->user()->can('bills.edit.dispatch.*')) {
                $model->drivers = $employeeRepo->GetDriverList();
            }
            if($req->user()->can('bills.edit.billing.*')) {
                $model->repeat_intervals = $selectionsRepo->GetSelectionsListByType('repeat_interval');
            }
        } else if(count($model->authenticatedAccountUsers) > 0) {
            $model->contact = $contactRepo->GetById($model->authenticatedAccountUsers[0]->contact_id);
            $model->accounts = $accountRepo->List($req->user(), $req->user()->can('viewChildAccounts', $accountRepo->GetById($model->authenticatedAccountUsers[0]->account_id)));
        } else if($req->user()->hasRole('superAdmin')) {
            $model->accounts = $accountRepo->List(null);
            $model->invoice_intervals = $selectionsRepo->GetSelectionsListByType('invoice_interval');
            $model->parent_accounts = $accountRepo->GetParentAccountsList();
            $model->employees = $employeeRepo->GetEmployeesList(null);
            $model->drivers = $employeeRepo->GetDriverList(false);
            $model->contact = ['first_name' => $req->user()->email];
        }

        return $model;
    }

    public function GetAdminDashboardModel() {
        $comparisonDate = date('Y-m-d');
        $comparisonDate = strtotime($comparisonDate . ' + 90 days');

        $billRepo = new Repos\BillRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $chartModelFactory = new \App\Http\Models\Chart\ChartModelFactory();

        $model = new \stdClass();
        $model->employee_expiries = [];
        $employeeExpiries = $employeeRepo->GetEmployeesWithExpiries($comparisonDate);
        foreach($employeeExpiries as $employee) {
            if(strtotime($employee->drivers_license_expiration_date) < $comparisonDate)
                array_push($model->employee_expiries, ['employee_name' => $employee->employee_name, 'employee_id' => $employee->employee_id, 'type' => 'drivers_license_expiration_date', 'date' => $employee->drivers_license_expiration_date]);
            if(strtotime($employee->license_plate_expiration_date) < $comparisonDate)
                array_push($model->employee_expiries, ['employee_name' => $employee->employee_name, 'employee_id' => $employee->employee_id, 'type' => 'license_plate_expiration_date', 'date' => $employee->license_plate_expiration_date]);
            if(strtotime($employee->insurance_expiration_date) < $comparisonDate)
                array_push($model->employee_expiries, ['employee_name' => $employee->employee_name, 'employee_id' => $employee->employee_id, 'type' => 'insurance_expiration_date', 'date' => $employee->insurance_expiration_date]);
        }
        $model->employee_birthdays = $employeeRepo->GetEmployeeBirthdays();
        $model->ytd_chart = $chartModelFactory->GetAdminDashboardChart();
        $model->calendar_heat_chart = $chartModelFactory->GetCalendarHeatChart();

        return $model;
    }
}

?>
