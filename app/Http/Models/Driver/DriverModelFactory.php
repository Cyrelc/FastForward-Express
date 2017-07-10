<?php
namespace App\Http\Models\Driver;

use App\Http\Repos;
use App\Http\Models;
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
                $dvm->address = $addrRepo->GetByContactId($d->contact_id);
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

    public function GetCreateModel($request) {
        $model = new DriverFormModel();
        $model->driver = new \App\Driver();
        $model->contact = new \App\Contact();

        $model->driver->license_expiration = date('U');
        $model->driver->license_plate_expiration = date('U');
        $model->driver->insurance_expiration = date('U');
        $model->driver->dob = date('U');
        $model->driver->start_date = date('U');
        $model->driver->pickup_commission = 34;
        $model->driver->delivery_commission = 34;

        $model->emergency_contacts = [];
        $model->contact->pager = new \App\PhoneNumber();

        $model = $this->MergeOld($model, $request);

        return $model;
    }

    public function GetEditModel($request, $id) {
        $addressRepo = new Repos\AddressRepo();
        $driverRepo = new Repos\DriverRepo();
        $phoneNumberRepo = new Repos\PhoneNumberRepo();

        $contactsFactory = new Models\Partials\ContactsModelFactory();
        $contactFactory = new Models\Partials\ContactModelFactory();

        $model = new DriverFormModel();
        $model->driver = $driverRepo->GetById($id);
        $model->contact = $contactFactory->GetEditModel($model->driver->contact_id, true);
        $model->address = $addressRepo->GetByContactId($model->contact->contact_id);

        $model->contact->pager = $phoneNumberRepo->GetPagerByDriverContact($model->driver->contact_id);
        if ($model->contact->pager === null)
            $model->contact->pager = new \App\PhoneNumber();

        $model->driver->pickup_commission *= 100;
        $model->driver->delivery_commission *= 100;
        $model->driver->start_date = strtotime($model->driver->start_date);
        $model->driver->license_expiration = strtotime($model->driver->license_expiration);
        $model->driver->license_plate_expiration = strtotime($model->driver->license_plate_expiration);
        $model->driver->insurance_expiration = strtotime($model->driver->insurance_expiration);
        $model->driver->dob = strtotime($model->driver->dob);

        $model->emergency_contacts = $contactsFactory->GetEditModel($driverRepo->ListEmergencyContacts($model->driver->driver_id), true);
        $model->emergency_contacts[0]->is_primary = true;

        $model = $this->MergeOld($model, $request);
        return $model;
    }

    public function MergeOld($model, $req) {
        $contactCollector = new \App\Http\Collectors\ContactCollector();
        $driverCollector = new \App\Http\Collectors\DriverCollector();

        $model->driver = $driverCollector->Remerge($req, $model->driver);
        $model->contact = $contactCollector->RemergeContact($req, $model->contact, '','contact', true);
        $model->contact->pager = $contactCollector->RemergePhoneNumberSingle($req, $model->contact->pager, 'pager');
        $model->emergency_contacts = $contactCollector->Remerge($req, $model->emergency_contacts, true);

        return $model;
    }
}
