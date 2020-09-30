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
        return $model;
    }

    public function GetCreateModel() {
        $contactModelFactory = new \App\Http\Models\Partials\ContactModelFactory();

        $model = new Employee\EmployeeFormModel();
        
        $model->employee = new \App\Employee();
        $model->contact = $contactModelFactory->GetCreateModel();
        $model->contact->emails->types = null;

        $model->employee->dob = date('U');
        $model->employee->start_date = date('U');
        $model->employee->license_expiration = date('U');
        $model->employee->license_plate_expiration = date('U');
        $model->employee->insurance_expiration = date('U');

        $model->emergency_contacts = [];

        return $model;
    }

    public function GetEditModel($employeeId) {
        $activityLogRepo = new Repos\ActivityLogRepo();
        $addressRepo = new Repos\AddressRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $phoneNumberRepo = new Repos\PhoneNumberRepo();

        $contactsFactory = new Models\Partials\ContactsModelFactory();
        $contactFactory = new Models\Partials\ContactModelFactory();

        $model = new EmployeeFormModel();
        $model->employee = $employeeRepo->GetById($employeeId);
        $model->emergency_contacts = $employeeRepo->GetEmergencyContacts($employeeId);
        $model->contact = $contactFactory->GetEditModel($model->employee->contact_id, true);
        $model->address = $addressRepo->GetByContactId($model->contact->contact_id);

        $model->activity_log = $activityLogRepo->GetEmployeeActivityLog($model->employee->employee_id);
        foreach($model->activity_log as $key => $log)
            $model->activity_log[$key]->properties = json_decode($log->properties);

        return $model;
    }
}
