<?php
namespace App\Http\Models\Employee;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Employee;

class EmployeeModelFactory
{
    public function ListAll() {
        $model = new Employee\EmployeesModel();

        try {
            $employeeRepo = new Repos\EmployeeRepo();
            $addrRepo = new Repos\AddressRepo();
            $contactRepo = new Repos\ContactRepo();
            $pnRepo = new Repos\PhoneNumberRepo();
            // $billRepo = new Repos\BillRepo();

            $employees = $employeeRepo->ListAll();
            $employee_view_models = array();

            foreach ($employees as $e) {
                $employee_view_model = new Employee\EmployeeViewModel();

                $employee_view_model->employee = $e;
                $employee_view_model->contact = $contactRepo->GetById($e->contact_id);
                $employee_view_model->address = $addrRepo->GetByContactId($e->contact_id);
                $employee_view_model->phoneNumber = $pnRepo->GetContactPrimaryPhone($e->contact_id);
                $employee_view_model->contact->name = $employee_view_model->contact->first_name . ' ' . $employee_view_model->contact->last_name;
                // if has type driver
                // $employee_view_model->bills = $billRepo->CountByDriver($d->driver_id, date("Y-m-01"));

                array_push($employee_view_models, $employee_view_model);
            }

            $model->employees = $employee_view_models;
            $model->success = true;
        }
        catch(Exception $e) {
            //TODO: Error-specific friendly messages
            $model->friendlyMessage = 'Sorry, but an error has occurred. Please contact support.';
            $model->errorMessage = $e;
        }
        // dd($model);
        return $model;
    }

    public function ListEmergencyContacts($employee_id) {
        $employeeRepo = new Repos\EmployeeRepo();

        $emergencyContacts = $employeeRepo->ListEmergencyContacts($employee_id);

        return $emergencyContacts;
    }

    public function GetCreateModel($request) {
        $contactModelFactory = new \App\Http\Models\Partials\ContactModelFactory();

        $model = new Employee\EmployeeFormModel();
        
        $model->employee = new \App\Employee();
        $model->contact = $contactModelFactory->GetCreateModel();
        $model->contact->emails->types = null;
        $model->driver = new \App\Driver();

        $model->employee->dob = date('U');
        $model->employee->start_date = date('U');
        $model->driver->license_expiration = date('U');
        $model->license_plate_expiration = date('U');
        $model->driver->insurance_expiration = date('U');

        $model->emergency_contacts = [];

        unset($model->contact->contact_id);

        return $model;
    }

    public function GetEditModel($request, $id) {
        $addressRepo = new Repos\AddressRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $phoneNumberRepo = new Repos\PhoneNumberRepo();
        $driverRepo = new Repos\DriverRepo();

        $contactsFactory = new Models\Partials\ContactsModelFactory();
        $contactFactory = new Models\Partials\ContactModelFactory();

        $model = new EmployeeFormModel();
        $model->employee = $employeeRepo->GetById($id);
        $model->contact = $contactFactory->GetEditModel($model->employee->contact_id, true);
        $model->contact->emails->types = null;
        $model->address = $addressRepo->GetByContactId($model->contact->contact_id);
        $model->driver = $driverRepo->GetByEmployeeId($id);
        if (!isset($model->driver))
            $model->driver = new \App\Driver();

        $model->employee->start_date = strtotime($model->employee->start_date);
        $model->employee->dob = strtotime($model->employee->dob);
        if(isset($model->driver)) {
            $model->driver->license_expiration = strtotime($model->driver->license_expiration);
            $model->driver->license_plate_expiration = strtotime($model->driver->license_plate_expiration);
            $model->driver->insurance_expiration = strtotime($model->driver->insurance_expiration);
        }

        return $model;
    }
}
