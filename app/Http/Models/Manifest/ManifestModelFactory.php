<?php
namespace App\Http\Models\Manifest;

use App\Http\Repos;

class ManifestModelFactory{
    public function ListAll() {
        $manifestRepo = new Repos\ManifestRepo();
        $driverRepo = new Repos\DriverRepo();
        $billRepo = new Repos\BillRepo();

        $model = new ManifestsModel();
        $manifestViewModels = array();
        foreach($manifestRepo->ListAll() as $manifest) {
            $manifestViewModel = new ManifestViewModel();
            $manifestViewModel->manifest = $manifest;
            $manifestViewModel->driver_contact = $driverRepo->getContactByDriverId($manifest->driver_id);
            $manifestViewModel->bill_count = $billRepo->countByManifestId($manifest->manifest_id);
            array_push($manifestViewModels, $manifestViewModel);
        }

        $model->manifests = $manifestViewModels;

        return $model;
    }

    public function GetById($manifest_id) {
        $manifestRepo = new Repos\ManifestRepo();
        $billRepo = new Repos\BillRepo();
        $driverRepo = new Repos\DriverRepo();
        $addressRepo = new Repos\AddressRepo();
        $accountRepo = new Repos\AccountRepo();

        $model = new ManifestViewModel();

        $model->manifest = $manifestRepo->GetById($manifest_id);
        $model->bill_count = $billRepo->CountByManifestId($manifest_id);
        $model->driver = $driverRepo->GetById($model->manifest->driver_id);
        $model->driver->contact = $driverRepo->GetContactByDriverId($model->driver->driver_id);

        $bills = $billRepo->GetByManifestId($manifest_id);
        foreach($bills as $bill) {
            $line = new ManifestLine();
            $line->date = $bill->date;
            $line->bill_amount = $bill->amount;
            $line->bill_id = $bill->bill_id;
            $line->account_name = $accountRepo->GetNameById($bill->charge_account_id);
            $line->delivery_type = $bill->delivery_type;

            if($bill->pickup_manifest_id == $manifest_id) {
                $pickup_line = clone($line);
                $pickup_line->type = 'Pickup';
                $pickup_line->driver_amount = number_format($bill->amount * $model->driver->pickup_commission, 2);
                $model->driver_total += $pickup_line->driver_amount;
                array_push($model->lines, $pickup_line);
            }
            if($bill->delivery_manifest_id == $manifest_id) {
                $line->type = 'Delivery';
                $line->driver_amount = number_format($bill->amount * $model->driver->delivery_commission, 2);
                $model->driver_total += $line->driver_amount;
                array_push($model->lines, $line);
            }
        }

        $model->driver_total = number_format($model->driver_total, 2);

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
