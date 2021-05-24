<?php
namespace App\Http\Models\Employee;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Employee;

class EmployeeModelFactory
{
    public function ListAll() {
        $model = new Employee\EmployeesModel();

        $employeeRepo = new Repos\EmployeeRepo();
        $addrRepo = new Repos\AddressRepo();
        $contactRepo = new Repos\ContactRepo();
        $pnRepo = new Repos\PhoneNumberRepo();

        $employees = $employeeRepo->ListAll();
        $employee_view_models = array();

        foreach ($employees as $employee) {
            $employee_view_model = new Employee\EmployeeViewModel();

            $employee_view_model->employee = $employee;
            $employee_view_model->contact = $contactRepo->GetById($employee->contact_id);
            $employee_view_model->address = $addrRepo->GetByContactId($employee->contact_id);
            $employee_view_model->phoneNumber = $pnRepo->GetContactPrimaryPhone($employee->contact_id);
            $employee_view_model->contact->name = $employee_view_model->contact->first_name . ' ' . $employee_view_model->contact->last_name;

            array_push($employee_view_models, $employee_view_model);
        }

        $model->employees = $employee_view_models;
        $model->success = true;

        return $model;
    }

    public function GetCreateModel($permissions) {
        $contactModelFactory = new \App\Http\Models\Partials\ContactModelFactory();
        $permissionModelFactory = new \App\Http\Models\Permission\PermissionModelFactory();

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

        $model->permissions = $permissions;
        $model->employee_permissions = $permissionModelFactory->GetEmployeeModelPermissions(null);

        return $model;
    }

    public function GetEditModel($employeeId, $permissions) {
        $activityLogRepo = new Repos\ActivityLogRepo();
        $addressRepo = new Repos\AddressRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $phoneNumberRepo = new Repos\PhoneNumberRepo();
        $userRepo = new Repos\UserRepo();

        $contactsFactory = new Models\Partials\ContactsModelFactory();
        $contactFactory = new Models\Partials\ContactModelFactory();
        $permissionModelFactory = new Models\Permission\PermissionModelFactory();

        $user = $userRepo->GetUserByEmployeeId($employeeId);

        $model = new EmployeeFormModel();
        $model->permissions = $permissions;
        $model->employee = $employeeRepo->GetById($employeeId, $permissions);
        $model->emergency_contacts = $employeeRepo->GetEmergencyContacts($employeeId);
        $model->contact = $contactFactory->GetEditModel($model->employee->contact_id, true);
        $model->address = $addressRepo->GetByContactId($model->contact->contact_id);
        $model->employee_permissions = $permissionModelFactory->GetEmployeeModelPermissions($model->employee->employee_id);

        if($permissions['viewActivityLog']) {
            $model->activity_log = $activityLogRepo->GetEmployeeActivityLog($model->employee->employee_id);
            foreach($model->activity_log as $key => $log)
                $model->activity_log[$key]->properties = json_decode($log->properties);
        }

        return $model;
    }
}
