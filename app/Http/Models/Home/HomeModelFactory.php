<?php
namespace App\Http\Models\Home;

use App\Models\Employee;
use App\Http\Repos;
use App\Http\Models;
use Illuminate\Support\Facades\Auth;
use DB;

class HomeModelFactory {
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
