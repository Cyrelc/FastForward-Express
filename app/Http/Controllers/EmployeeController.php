<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Employee;
use App\Http\Collectors;
use App\Http\Validation;

use Validator;

class EmployeeController extends Controller {

    public function buildTable() {
        $employeeRepo = new Repos\EmployeeRepo();
        return $employeeRepo->ListAll();
    }

    public function __construct() {
        $this->middleware('auth');
    }

    public function deleteEmergencyContact(Request $req) {
        DB::beginTransaction();
        try {
            $contactRepo = new Repos\ContactRepo();
            $contactRepo->DeleteEmployeeEmergencyContact($req->employee_id, $req->contact_id);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success'=> false,
                'error'=>$e->getMessage()
            ]);
        }
    }

    public function getEmergencyContactModel($id = null) {
        $contactModelFactory = new \App\Http\Models\Partials\ContactModelFactory();
        if($id)
            $model = $contactModelFactory->GetEditModel($id, true);
        else
            $model = $contactModelFactory->GetCreateModel();
        return json_encode($model);
    }

    public function getModel(Request $req, $id = null) {
        $employeeModelFactory = new Employee\EmployeeModelFactory();
        if($id) {
            $id = strtoupper($id);
            if($id[0] === 'N') {
                $employeeRepo = new Repos\EmployeeRepo();
                $id = substr($id, 1);
                $id = $employeeRepo->GetEmployeeIdByEmployeeNumber($id);
            }
            $model = $employeeModelFactory->GetEditModel($id);
        }
        else
            $model = $employeeModelFactory->GetCreateModel();
        return json_encode($model);
    }

    // public function setPrimaryEmergencyContact($employeeId, $contactId) {
    //     $contactRepo = new Repos\ContactRepo();
    //     $contactRepo->SetEmployeePrimaryEmergencyContact($employeeId, $contactId);
    // }

    public function storeEmergencyContact(Request $req) {
        DB::beginTransaction();
        try {
            $partialsValidation = new \App\Http\Validation\PartialsValidationRules();

            $temp = $partialsValidation->GetContactValidationRulesV2($req);

            $this->validate($req, $temp['rules'], $temp['messages']);

            $contactCollector = new \App\Http\Collectors\ContactCollector();

            //Begin Contact
            $contactId = $req->contact_id;
            $contactRepo = new Repos\ContactRepo();
            $contact = $contactCollector->GetContact($req);
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
        } catch (exception $e) {
            DB::rollBack();

            return response()->json([
                'success'=> false,
                'error'=>$e->getMessage()
            ]);
        }
    }

    public function store(Request $req) {
        DB::beginTransaction();

        $employeeValidator = new Validation\EmployeeValidationRules();
        $partialsValidator = new Validation\PartialsValidationRules();

        $contactRepo = new Repos\ContactRepo();
        $employeeRepo = new Repos\EmployeeRepo();
        $userRepo = new Repos\UserRepo();

        $employeeId = $req->input('employee_id');
        $employee = $employeeRepo->GetById($employeeId);
        $userId = $employeeId ? $userRepo->GetUserByEmployeeId($employeeId)->user_id : null;
        $contactId = $employeeId ? $contactRepo->GetById($employee->contact_id)->contact_id : null;
        $isEdit = isset($req->employee_id) && $req->employee_id !== '';

        $employeeRules = $employeeValidator->GetValidationRules($req);
        $contactRules = $partialsValidator->GetContactValidationRulesv2($req, $userId, $contactId);

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
        if($isEdit) {
            $userId = $userRepo->Update($user)->user_id;
            $contactId = $contactRepo->Update($contact)->contact_id;
        } else {
            $userId = $userRepo->Insert($user)->user_id;
            $contactId = $contactRepo->Insert($contact)->contact_id;
        }

        $contactCollector->ProcessEmailAddressesForContact($req, $contactId);
        $contactCollector->ProcessPhoneNumbersForContact($req, $contactId);
        $contactCollector->ProcessAddressForContact($req, $contactId);

        $employee = $employeeCollector->Collect($req, $contactId, $userId);
        if ($isEdit) {
            $employeeRepo->Update($employee);
        } else {
            $employeeId = $employeeRepo->Insert($employee)->employee_id;
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'employee_id' => $employeeId
        ]);
    }

    public function toggleActive($employeeId) {
        DB::beginTransaction();
        try {
            $employeeRepo = new Repos\EmployeeRepo();

            $employeeRepo->ToggleActive($employeeId);

            DB::commit();
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
