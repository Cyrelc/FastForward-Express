<?php
namespace App\Http\Models\Home;

use App\Http\Repos;
use App\Http\Models;
use Illuminate\Support\Facades\Auth;

class HomeModelFactory {
    public function GetAppConfiguration($req) {
        $accountRepo = new Repos\AccountRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $paymentRepo = new Repos\PaymentRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $model = new AppModel();

        $model->payment_types = $paymentRepo->GetPaymentTypesList();

        if($req->user()->employee) {
            if($req->user()->can('viewAll', Account::class) || $req->user()->can('bills.view.basic.*'))
                $model->accounts = $accountRepo->List(null);
            if($req->user()->can('viewAll', Account::class)) {
                $model->invoice_intervals = $selectionsRepo->GetSelectionsListByType('invoice_interval');
                $model->parent_accounts = $accountRepo->GetParentAccountsList();
            }
            if($req->user()->can('viewAll', Employee::class) || $req->user()->can('bills.view.dispatch.*')) {
                $model->employees = $employeeRepo->GetEmployeesList($req->user()->can('viewAll', Employee::class) ? null : $req->user()->employee->employee_id);
            }
            if($req->user()->can('bills.edit.dispatch.*')) {
                $model->drivers = $employeeRepo->GetDriverList();
            }
            if($req->user()->can('bills.edit.billing.*')) {
                $model->repeat_intervals = $selectionsRepo->GetSelectionsListByType('repeat_interval');
            }
        } else if(count($req->user()->accountUsers) > 0) {
            $model->accounts = $accountRepo->List($req->user(), $req->user()->can('viewChildAccounts', $accountRepo->GetById($req->user()->accountUsers[0]->account_id)));
        } else if($req->user()->hasRole('superAdmin')) {
            $model->accounts = $accountRepo->List(null);
            $model->invoice_intervals = $selectionsRepo->GetSelectionsListByType('invoice_interval');
            $model->parent_accounts = $accountRepo->GetParentAccountsList();
            $model->employees = $employeeRepo->GetEmployeesList(null);
            $model->drivers = $employeeRepo->GetDriverList(false);
        }

        return $model;
    }

    public function GetAdminDashboardModel() {
        $comparisonDate = date('Y-m-d');
        $comparisonDate = strtotime($comparisonDate . ' + 90 days');

        $appsettingsRepo = new Repos\ApplicationSettingsRepo();
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
        $model->upcoming_holidays = $appsettingsRepo->GetUpcomingHolidays();

        return $model;
    }
}

?>
