<?php
namespace App\Http\Models\Home;

use App\Employee;
use App\Http\Repos;
use App\Http\Models;
use Illuminate\Support\Facades\Auth;
use DB;

class HomeModelFactory {
    public function GetAppConfiguration($req) {
        $accountRepo = new Repos\AccountRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $paymentRepo = new Repos\PaymentRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $model = new AppModel();

        $model->payment_types = $paymentRepo->GetPaymentTypesList();

        if($req->user()->employee || $req->user()->hasRole('superAdmin')) {
            if($req->user()->can('viewAll', Account::class) || $req->user()->can('bills.view.basic.*'))
                $model->accounts = $accountRepo->List(null);
            if($req->user()->can('viewAll', Account::class)) {
                $model->invoice_intervals = $selectionsRepo->GetSelectionsListByType('invoice_interval');
                $model->parent_accounts = $accountRepo->GetParentAccountsList();
            }
            if($req->user()->can('viewAll', Employee::class) || $req->user()->can('bills.view.dispatch.*')) {
                $model->employees = Employee::leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
                ->select(
                    DB::raw('concat(employee_number, " - ", coalesce(preferred_name , concat(first_name, " ", last_name))) as label'),
                    'employee_id as value'
                )->get();
            }
            if($req->user()->can('bills.edit.dispatch.*')) {
                $model->drivers = $employeeRepo->getDriverList();
            }
            if($req->user()->can('bills.edit.billing.*')) {
                $model->repeat_intervals = $selectionsRepo->GetSelectionsListByType('repeat_interval');
            }
        } else if(count($req->user()->accountUsers) > 0) {
            $model->accounts = $accountRepo->List($req->user(), $req->user()->can('viewChildAccounts', $accountRepo->GetById($req->user()->accountUsers[0]->account_id)));
        }

        return $model;
    }

    public function GetAdminDashboardModel() {
        $comparisonDate = (new \DateTime())->modify('+90 days');

        $appsettingsRepo = new Repos\ApplicationSettingsRepo();
        $billRepo = new Repos\BillRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $chartModelFactory = new \App\Http\Models\Chart\ChartModelFactory();

        $model = new \stdClass();
        $model->employee_expiries = [];
        $employeeExpiries = $employeeRepo->getEmployeesWithExpiries($comparisonDate);
        foreach($employeeExpiries as $employee) {
            if(new \DateTime($employee->drivers_license_expiration_date) < $comparisonDate)
                array_push($model->employee_expiries, ['employee_name' => $employee->employee_name, 'employee_id' => $employee->employee_id, 'type' => 'Drivers License', 'date' => $employee->drivers_license_expiration_date]);
            if(new \DateTime($employee->license_plate_expiration_date) < $comparisonDate)
                array_push($model->employee_expiries, ['employee_name' => $employee->employee_name, 'employee_id' => $employee->employee_id, 'type' => 'License Plate', 'date' => $employee->license_plate_expiration_date]);
            if(new \DateTime($employee->insurance_expiration_date) < $comparisonDate)
                array_push($model->employee_expiries, ['employee_name' => $employee->employee_name, 'employee_id' => $employee->employee_id, 'type' => 'Insurance', 'date' => $employee->insurance_expiration_date]);
        }
        $model->employee_birthdays = $employeeRepo->getEmployeeBirthdays();
        $model->ytd_chart = $chartModelFactory->GetAdminDashboardChart();
        $model->calendar_heat_chart = $chartModelFactory->GetCalendarHeatChart();
        $model->upcoming_holidays = $appsettingsRepo->GetUpcomingHolidays();

        return $model;
    }
}

?>
