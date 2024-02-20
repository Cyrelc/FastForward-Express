<?php

namespace App\Services;

use DB;
use App\Models\Employee;
use App\Http\Repos\PermissionRepo;
use App\Http\Resources\CreateEmployeeResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeListResource;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class EmployeeService {
    private $contactService;
    private $userService;

    public function __construct(
        ContactService $contactService,
        UserService $userService,
    ) {
        $this->contactService = $contactService;
        $this->userService = $userService;
    }

    public function create($employeeData) {
        DB::beginTransaction();

        $contact = $this->contactService->create($employeeData['contact']);

        $userData = [
            'email' => $contact->primary_email->email,
            'is_enabled' => $employeeData['is_enabled']
        ];

        $user = $this->userService->create($userData);

        $employeeData['user_id'] = $user->user_id;
        $employeeData['contact_id'] = $contact->contact_id;

        $employee = Employee::create($employeeData);
        // TODO - move permissions to be a user (or permissions?) responsibility, because that's the model they reference
        $this->handlePermissions($employeeData['permissions'], $user);

        DB::commit();

        return $employee;
    }

    public function createEmergencyContact($emergencyContactData, $employeeId) {
        $contact = $this->contactService->create($emergencyContactData);

        $employeeEmergencyContactData = [
            'contact_id' => $contact->contact_id,
            'employee_id' => $employeeId
        ];

        $employeeEmergencyContact = EmployeeEmergencyContact::create($employeeEmergencyContactData);

        return $employeeEmergencyContact;
    }

    public function deleteEmergencyContact($contactId) {
        DB::beginTransaction();

        $emergencyContact = EmployeeEmergencyContact::firstOrFail('contact_id', $contactId);
        $emergencyContact->delete();
        $this->contactService->delete($emergencyContact->contact_id);

        DB::commit();

        return true;
    }

    public function getEmergencyContact($contactId) {
        $emergencyContact = EmployeeEmergencyContact::firstOrFail($contactId);

        return $emergencyContact;
    }

    public function list($enabledOnly = false) {
        $employees = Employee::leftJoin('users', 'users.user_id', 'employees.user_id');

        if($enabledOnly)
            $employees->where('is_enabled', true);

        QueryBuilder::for($employees)
            ->allowedFilters([
                AllowedFilter::exact('is_enabled', 'users.is_enabled')
            ]);

        return EmployeeListResource::collection($employees->get());
    }

    public function update($employeeData) {
        DB::beginTransaction();

        $employee = Employee::find($employeeData['employee_id']);
        $employeeData['contact']['contact_id'] = $employee->contact->contact_id;
        $employeeData['user_id'] = $employee->user->user_id;

        $contact = $this->contactService->update($employeeData['contact']);

        $employee->update($employeeData);
        if($employee->user->email != $employee->contact->primary_email)
            $employee->user->update(['email' => $employee->contact->primary_email->email]);

        if(Auth::user()->can('updateAdvanced', $employee))
            $employee->user->update(['is_enabled' => $employeeData['is_enabled']]);
        /**
         * Begin permissions
         */
        if(Auth::user()->can('updatePermissions', $employee))
            $this->handlePermissions($employeeData['permissions'], $employee->user);
        /**
         * End Permissions
         */
        DB::commit();
        return $employee;
    }

    public function updateEmergencyContact($emergencyContactData) {

    }

    // Private functions

    private function handlePermissions($permissions, $user) {
        $processedPermissions = [];
        foreach(Employee::$permissionsMap as $key => $value)
            $processedPermissions[$key] = $permissions[$value];

        $permissionRepo = new \App\Http\Repos\PermissionRepo();
        $permissionRepo->assignUserPermissions($user, $processedPermissions);
    }
}

