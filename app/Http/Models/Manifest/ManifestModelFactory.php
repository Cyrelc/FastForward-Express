<?php
namespace App\Http\Models\Manifest;

use App\Http\Repos;

class ManifestModelFactory{
    public function ListAll() {
        $manifestRepo = new Repos\ManifestRepo();

        return $manifestRepo->ListAll();
    }

    public function GetById($manifest_id) {
        $accountRepo = new Repos\AccountRepo();
        $addressRepo = new Repos\AddressRepo();
        $billRepo = new Repos\BillRepo();
        $chargebackRepo = new Repos\ChargebackRepo();
        $driverRepo = new Repos\DriverRepo();
        $manifestRepo = new Repos\ManifestRepo();
        $phoneRepo = new Repos\PhoneNumberRepo();

        $model = new ManifestViewModel();

        $model->manifest = $manifestRepo->GetById($manifest_id);
        $model->bill_count = $billRepo->CountByManifestId($manifest_id);
        $model->driver = $driverRepo->GetById($model->manifest->driver_id);
        $model->driver->contact = $driverRepo->GetContactByDriverId($model->driver->driver_id);
        $model->driver->contact->primary_phone = $phoneRepo->GetContactPrimaryPhone($model->driver->contact->contact_id)->phone_number;
        $model->driver->address = $addressRepo->GetByContactId($model->driver->contact->contact_id);

        $model->bills = $billRepo->GetByManifestId($manifest_id);
        $model->overview = $billRepo->GetManifestOverviewById($manifest_id);

        $driver_total = $billRepo->GetDriverTotalByManifestId($manifest_id);
        $model->driver_total = number_format($driver_total, 2); 

        $model->chargebacks = $chargebackRepo->GetByManifestId($manifest_id);

        $chargeback_total = $chargebackRepo->GetChargebackTotalByManifestId($manifest_id);
        $model->chargeback_total = number_format($chargeback_total, 2);

        $model->driver_income = number_format($driver_total - $chargeback_total, 2);

        return $model;
    }

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
