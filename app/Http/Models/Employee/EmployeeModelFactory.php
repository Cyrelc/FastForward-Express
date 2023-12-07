<?php
namespace App\Http\Models\Employee;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Employee;

class EmployeeModelFactory
{
    public function ListAll() {
        $employeeRepo = new Repos\EmployeeRepo();

        $model = new Employee\EmployeesModel();
        $model->employees = [];

        $employees = $employeeRepo->ListAll();

        foreach ($employees as $employee)
            array_push($model->employees, $this->GetViewModel($employee->employee_id));

        $model->success = true;

        return $model;
    }

    public function GetCreateModel($permissions) {
        $contactModelFactory = new \App\Http\Models\Partials\ContactModelFactory();
        $permissionModelFactory = new \App\Http\Models\Permission\PermissionModelFactory();

        $selectionsRepo = new Repos\SelectionsRepo();

        $model = new Employee\EmployeeViewModel();
        
        $model->employee = new \App\Employee();
        $model->contact = $contactModelFactory->GetCreateModel();
        $model->contact->emails->types = null;

        $model->employee->dob = date('U');
        $model->employee->start_date = date('U');
        $model->employee->license_expiration = date('U');
        $model->employee->license_plate_expiration = date('U');
        $model->employee->insurance_expiration = date('U');

        $model->permissions = $permissions;
        $model->employee_permissions = $permissionModelFactory->GetEmployeeModelPermissions(null);
        $model->vehicle_types = $selectionsRepo->GetSelectionsByType('vehicle_type');

        return $model;
    }

    public function GetEditModel($employeeId, $permissions) {
        $activityLogRepo = new Repos\ActivityLogRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $selectionsRepo = new Repos\SelectionsRepo();
        $userRepo = new Repos\UserRepo();

        $contactsFactory = new Models\Partials\ContactsModelFactory();
        $permissionModelFactory = new Models\Permission\PermissionModelFactory();

        $user = $userRepo->GetUserByEmployeeId($employeeId);

        $model = $this->GetViewModel($employeeId);
        $model->permissions = $permissions;
        $model->employee_permissions = $permissionModelFactory->GetEmployeeModelPermissions($model->employee->employee_id);
        $model->vehicle_types = $selectionsRepo->GetSelectionsByType('vehicle_type');

        if($permissions['viewActivityLog']) {
            $model->activity_log = $activityLogRepo->GetEmployeeActivityLog($model->employee->employee_id);
            foreach($model->activity_log as $key => $log)
                $model->activity_log[$key]->properties = json_decode($log->properties);
        }

        return $model;
    }

    public function GetViewModel($employeeId) {
        $contactModelFactory = new Models\Partials\ContactModelFactory();

        $addressRepo = new Repos\AddressRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $phoneRepo = new Repos\PhoneNumberRepo();

        $model = new EmployeeViewModel();

        $model->employee = $employeeRepo->GetById($employeeId);
        $model->contact = $contactModelFactory->GetEditModel($model->employee->contact_id, true);
        $model->phone_number = $phoneRepo->GetContactPrimaryPhone($model->contact->contact_id)->phone_number;
        $model->address = $addressRepo->GetByContactId($model->contact->contact_id);

        // handle checking whether anything has expired, or is soon to expire
        $expirations = ['drivers_license_expiration_date' => 'Drivers License', 'license_plate_expiration_date' => 'License Plate', 'insurance_expiration_date' => 'Vehicle Insurance'];
        $model->warnings = [];
        $currentDate = new \DateTime();
        $datePlusNinetyDays = (new \DateTime())->modify('+90 days');
        foreach($expirations as $dbName => $friendlyString)
            if(new \DateTime($model->employee->$dbName) < $currentDate)
                array_push($model->warnings, ['friendlyString' => $friendlyString . ' has expired', 'type' => 'error']);
            else if(new \DateTime($model->employee->$dbName) < $datePlusNinetyDays)
                array_push($model->warnings, ['friendlyString' => $friendlyString . ' will expire soon', 'type' => 'warning']);

        return $model;
    }
}
