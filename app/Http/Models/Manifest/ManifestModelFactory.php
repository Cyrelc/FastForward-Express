<?php
namespace App\Http\Models\Manifest;

use App\Http\Repos;

class ManifestModelFactory{
    public function GetGenerateModel($start_date = null, $end_date = null) {
        if(isset($start_date) && isset($end_date)) {
            $start_date = (new \DateTime($start_date))->format('Y-m-d');
            $end_date = (new \DateTime($end_date))->format('Y-m-d');
        } else {
            $start_date = date('U', strtotime('first day of previous month'));
            $end_date = date('U', strtotime('last day of previous month'));
        }

        $model = new GenerateManifestViewModel();

        $model->start_date = $start_date;
        $model->end_date = $end_date;

        return $model;
    }

    public function GetDriverListModel($start_date, $end_date) {
        $driverRepo = new Repos\DriverRepo();
        $billRepo = new Repos\BillRepo();
        $contactRepo = new Repos\ContactRepo();
        $employeeRepo = new Repos\EmployeeRepo();

        $model = new DriverListModel();

        $drivers = $driverRepo->ListAll();
        foreach($drivers as $driver) {
            $driver->bill_count = $billRepo->CountByDriverBetweenDates($driver->driver_id, date('Y-m-d', strtotime($start_date)), date('Y-m-d', strtotime($end_date)));
            if($driver->bill_count == 0)
                continue;
            $driver->employee = $employeeRepo->GetById($driver->employee_id);
            $driver->contact = $contactRepo->GetById($driver->employee->contact_id);
            array_push($model->drivers, $driver);
        }

        return $model;
    }
}
