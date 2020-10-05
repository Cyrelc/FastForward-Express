<?php
namespace App\Http\Models\Dashboard;

use App\Http\Repos;
use App\Http\Models\Dashboard;

class DashboardModelFactory{
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

        return $model;
    }
}

?>
