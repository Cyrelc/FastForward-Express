<?php
namespace app\Http\Models\Driver;

use App\Http\Repos;
use App\Http\Models\Driver;

class DriverModelFactory
{
    public function ListAll() {
        $model = new Driver\DriversModel();

        try {
            $driversRepo = new Repos\DriverRepo();
            $addrRepo = new Repos\AddressRepo();
            $contactRepo = new Repos\ContactRepo();
            $pnRepo = new Repos\PhoneNumberRepo();
            $billRepo = new Repos\BillRepo();

            $drivers = $driversRepo->ListAll();
            $dvms = array();

            foreach ($drivers as $d) {
                $dvm = new Driver\DriverViewModel();

                $dvm->driver = $d;
                $dvm->contact = $contactRepo->GetById($d->contact_id);
                $dvm->address = $addrRepo->ListByContactId($d->contact_id);
                $dvm->phoneNumber = $pnRepo->GetById($d->contact_id);
                $dvm->driver->insurance_expiration = date("l, F d Y", strtotime($d->insurance_expiration));
                $dvm->driver->license_expiration = date("l, F d Y", strtotime($d->license_expiration));
                $dvm->contact->name = $dvm->contact->first_name . ' ' . $dvm->contact->last_name;
                $dvm->bills = $billRepo->CountByDriver($d->driver_id, date("Y-m-01"));

                array_push($dvms, $dvm);
            }

            $model->drivers = $dvms;
            $model->success = true;
        }
        catch(Exception $e) {
            //TODO: Error-specific friendly messages
            $model->friendlyMessage = 'Sorry, but an error has occurred. Please contact support.';
            $model->errorMessage = $e;
        }

        return $model;
    }
}
