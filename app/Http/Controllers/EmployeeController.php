<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Collectors;
use App\Http\Validation;
use App\Http\Models\Employee;

use Validator;

class EmployeeController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }

    public function buildTable(Request $req) {
        if($req->user()->cannot('viewAll', Employee::class))
            abort(403);

        $employeeRepo = new Repos\EmployeeRepo();
        $employees = $employeeRepo->ListAll($req);

        return json_encode($employees);
    }

    public function deleteEmergencyContact(Request $req) {
        $employeeRepo = new Repos\EmployeeRepo();
        $employee = $employeeRepo->GetById($req->employee_id);
        if($req->user()->cannot('updateBasic', $employee))
            abort(403);

        DB::beginTransaction();

        $contactRepo = new Repos\ContactRepo();
        $contactRepo->DeleteEmployeeEmergencyContact($req->employee_id, $req->contact_id);

        DB::commit();
        return response()->json([
            'success' => true,
            'emergency_contacts' => $employeeRepo->GetEmergencyContacts($employee->employee_id)
        ]);
    }

    public function getEmergencyContactModel(Request $req, $contactId = null) {
        $contactModelFactory = new \App\Http\Models\Partials\ContactModelFactory();

        if($contactId) {
            $employeeRepo = new Repos\EmployeeRepo();
            $emergencyContact = $employeeRepo->GetEmergencyContactByContactId($contactId);
            $employee = $employeeRepo->GetById($emergencyContact->employee_id, null);
            if(!$employee || $req->user()->cannot('updateBasic', $employee))
                abort(403);

            $model = $contactModelFactory->GetEditModel($contactId, true);
        } else
            $model = $contactModelFactory->GetCreateModel();

        return json_encode($model);
    }

    public function getModel(Request $req, $employeeId = null) {
        $permissionModelFactory = new \App\Http\Models\Permission\PermissionModelFactory();

        $employeeModelFactory = new Employee\EmployeeModelFactory();
        if($employeeId) {
            $employeeId = strtoupper($employeeId);
            if($employeeId[0] === 'N') {
                $employeeRepo = new Repos\EmployeeRepo();
                $employeeId = substr($employeeId, 1);
                $employeeId = $employeeRepo->GetEmployeeIdByEmployeeNumber($employeeId);
            }

            $employeeRepo = new Repos\EmployeeRepo();
            $employee = $employeeRepo->GetById($employeeId, null);
            if($req->user()->cannot('viewBasic', $employee))
                abort(403);

            $permissions = $permissionModelFactory->GetEmployeePermissions($req->user(), $employee);

            $model = $employeeModelFactory->GetEditModel($employeeId, $permissions);
        } else {
            if($req->user()->cannot('create', Employee::class))
                abort(403);

            $permissions = $permissionModelFactory->GetEmployeePermissions($req->user());
            $model = $employeeModelFactory->GetCreateModel($permissions);
        }

        return json_encode($model);
    }

    public function store(Request $req) {
        DB::beginTransaction();

        $employeeValidator = new Validation\EmployeeValidationRules();
        $partialsValidator = new Validation\PartialsValidationRules();

        $contactRepo = new Repos\ContactRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $userRepo = new Repos\UserRepo();

        $permissionModelFactory = new \App\Http\Models\Permission\PermissionModelFactory();

        $employeeId = $req->input('employee_id');
        $oldEmployee = $employeeRepo->GetById($employeeId, null);
        if($oldEmployee ? $req->user()->cannot('updateBasic', $oldEmployee) : $req->user()->cannot('create', Employee::class))
            abort(403);

        $permissions = $permissionModelFactory->GetEmployeePermissions($req->user(), $oldEmployee ? $oldEmployee : null);

        $userId = $oldEmployee? $oldEmployee->user_id : null;
        $contactId = $oldEmployee ? $oldEmployee->contact_id : null;

        $employeeRules = $employeeValidator->GetValidationRules($req, $permissions, $oldEmployee);
        $contactRules = $partialsValidator->GetContactValidationRules($req, $userId, $contactId);

        $validationRules = [];
        $validationMessages = [];

        $validationRules = array_merge($validationRules, $contactRules['rules']);
        $validationMessages = array_merge($validationMessages, $contactRules['messages']);
        $validationRules = array_merge($validationRules, $employeeRules['rules']);
        $validationMessages = array_merge($validationMessages, $employeeRules['messages']);

        $this->validate($req, $validationRules, $validationMessages);

        $contactCollector = new Collectors\ContactCollector();
        $employeeCollector = new Collectors\EmployeeCollector();

        $contact = $contactCollector->GetContact($req, $contactId);

        $userCollector = new Collectors\UserCollector();
        $user = $userCollector->CollectUserForEmployee($req);

        //Begin User
        if($oldEmployee) {
            $user = $userRepo->Update($user, $permissions['editAdvanced']);
            $contactId = $contactRepo->Update($contact)->contact_id;
        } else {
            $user = $userRepo->Insert($user);
            $contactId = $contactRepo->Insert($contact)->contact_id;
        }

        $contactCollector->ProcessEmailAddressesForContact($req, $contactId);
        $contactCollector->ProcessPhoneNumbersForContact($req, $contactId);
        $contactCollector->ProcessAddressForContact($req, $contactId);

        $employee = $employeeCollector->Collect($req, $contactId, $user->user_id, $permissions);
        if ($oldEmployee) {
            $employee = $employeeRepo->Update($employee, $permissions);
        } else {
            $employee = $employeeRepo->Insert($employee);
        }
        //End User
        /**
         * Begin permissions
         */
        if($req->user()->can('updatePermissions', $oldEmployee)) {
            $permissions = $userCollector->CollectEmployeePermissions($req);
            $permissionRepo = new Repos\PermissionRepo();
            $permissionRepo->assignUserPermissions($user, $permissions);
        }
        /**
         * End Permissions
         */

        DB::commit();
        return response()->json([
            'success' => true,
            'employee_id' => $employee->employee_id,
            'updated_at' => $employee->updated_at
        ]);
    }

    public function storeEmergencyContact(Request $req) {
        DB::beginTransaction();

        $employeeRepo = new Repos\EmployeeRepo();
        $employee = $employeeRepo->GetById($req->employee_id);
        if($req->user()->cannot('updateBasic', $employee))
            abort(403);

        $partialsValidation = new \App\Http\Validation\PartialsValidationRules();

        $temp = $partialsValidation->GetContactValidationRules($req);

        $this->validate($req, $temp['rules'], $temp['messages']);

        $contactCollector = new \App\Http\Collectors\ContactCollector();

        //Begin Contact
        $contactId = $req->contact_id;
        $contactRepo = new Repos\ContactRepo();
        $contact = $contactCollector->GetContact($req, $contactId);

        if($contactId)
            $contactRepo->Update($contact);
        else {
            $contactId = $contactRepo->Insert($contact)->contact_id;
            $employeeCollector = new \App\Http\Collectors\EmployeeCollector();
            $employeeEmergencyContact = $employeeCollector->CollectEmergencyContact($req, $contactId);
            $employeeRepo = new Repos\EmployeeRepo();
            $employeeRepo->AddEmergencyContact($employeeEmergencyContact);
        }
        //End Contact
        $contactCollector->ProcessPhoneNumbersForContact($req, $contactId);
        $contactCollector->ProcessEmailAddressesForContact($req, $contactId);
        $contactCollector->ProcessAddressForContact($req, $contactId);

        DB::commit();
        return response()->json([
            'success' => true,
            'emergency_contacts' => $employeeRepo->GetEmergencyContacts($employee->employee_id)
        ]);
    }

    public function toggleActive(Request $req, $employeeId) {
        $employeeRepo = new Repos\EmployeeRepo();
        $employee = $employeeRepo->GetById($employeeId, null);
        if($req->user()->cannot('updateAdvanced', $employee))
            abort(403);

        DB::beginTransaction();

        $employeeRepo->ToggleActive($employeeId);

        DB::commit();
        return response()->json([
            'success' => true
        ]);
    }
}
