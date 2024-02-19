<?php

namespace App\Http\Controllers;


use App\Http\Repos;
use App\Http\Collectors;
use App\Employee;
use App\EmployeeEmergencyContact;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\EmergencyContactListResource;
use App\Http\Resources\CreateEmployeeResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeListResource;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use DB;
// use Validator;

class EmployeeController extends Controller {
    private $employeeService;

    public function __construct(EmployeeService $employeeService) {
        $this->middleware('auth');
        $this->employeeService = $employeeService;
    }

    public function create(StoreEmployeeRequest $req) {
        if(Auth::user()->cannot('create', Employee::class))
            abort(403);

        $employee = $this->employeeService->create($req->validated());

        return $employee;
    }

    public function deleteEmergencyContact(Request $req, $employeeId, $contactId) {
        $employeeRepo = new Repos\EmployeeRepo();
        $employee = $employeeRepo->getById($employeeId);
        if($req->user()->cannot('updateBasic', $employee))
            abort(403);

        DB::beginTransaction();

        $emloyee->emergency_contacts()->where(['contact_id', $contactId])->delete();
        $this->contactService->delete($contactid);

        DB::commit();
        return response()->json([
            'success' => true,
            'emergency_contacts' => $employeeRepo->getEmergencyContacts($employee->employee_id)
        ]);
    }

    public function getEmergencyContact(Request $req, $contactId = null) {
        if($contactId) {
            $employee = EmployeeEmergencyContact::firstOrFail('contact_id', $contactId)->employee;
            dd($employee);
            $employeeRepo = new Repos\EmployeeRepo();
            $emergencyContact = $employeeRepo->getEmergencyContactByContactId($contactId);
            $employee = Employee::find($emergencyContact->employee_id);
            if(!$employee || $req->user()->cannot('updateBasic', $employee))
                abort(403);

            $model = $contactModelFactory->GetEditModel($contactId, true);
        } else
            $model = $contactModelFactory->GetCreateModel();

        return json_encode($model);
    }


    public function getEmergencyContacts(Request $req, $employeeId) {
        $employee = Employee::find($employeeId);

        if($req->user()->cannot('viewBasic', $employee))
            abort(403);

        $emergencyContacts = $employee->emergency_contacts;

        return response()->json([
            'success' => true,
            'emergency_contacts' => EmergencyContactListResource::collection($emergencyContacts)
        ]);
    }

    public function getModel(Request $req, $employeeId = null) {
        if($employeeId) {
            if($employeeId[0] === 'N') {
                $employeeNumber = substr($employeeId, 1);
                $employee = Employee::where('employee_number', $employeeNumber)->firstOrFail();
            } else
                $employee = Employee::findOrFail($employeeId);

            if(Auth::user()->cannot('viewBasic', $employee))
                abort(403);

            $activityLogRepo = new Repos\ActivityLogRepo();

            $employee->activity_log = $activityLogRepo->GetEmployeeActivityLog($employee->employee_id);
            $model = new EmployeeResource($employee);
        } else {
            if(Auth::user()->cannot('create', Employee::class))
                abort(403);
            $model = new CreateEmployeeResource(new Employee());
        }

        return response()->json($model);
    }

    public function index(Request $req) {
        if(Auth::user()->cannot('viewAll', Employee::class))
            abort(403);

        $employees = $this->employeeService->list();

        $queryRepo = new Repos\QueryRepo();
        $queries = $queryRepo->GetByTable('employees');

        return response()->json([
            'success' => true,
            'data' => $employees,
            'queries' => $queries
        ]);
    }

    public function update(StoreEmployeeRequest $req, $employeeId) {
        if(Auth::user()->cannot('updateBasic', Employee::findOrFail($employeeId)))
            abort(403);

        $employee = $this->employeeService->update($req->validated());

        return response()->json([
            'success' => true,
            'employee_id' => $employee->employee_id,
            'updated_at' => $employee->updated_at
        ]);
    }

    public function storeEmergencyContact(Request $req, $employeeId) {
        DB::beginTransaction();

        $employeeRepo = new Repos\EmployeeRepo();
        $employee = $employeeRepo->getById($employeeId, null);
        if($req->user()->cannot('updateBasic', $employee))
            abort(403);

        $partialsValidation = new \App\Http\Validation\PartialsValidationRules();

        $temp = $partialsValidation->GetContactValidationRules($req);

        $this->validate($req, $temp['rules'], $temp['messages']);

        $contactCollector = new \App\Http\Collectors\ContactCollector();

        //Begin Contact
        $contactId = $req->contact_id;
        $contact = $contactCollector->GetContact($req, $contactId);

        if($contactId)
            $this->contactService->update($contact);
        else {
            $contactId = $this->contactService->insert($contact)->contact_id;
            $employeeCollector = new \App\Http\Collectors\EmployeeCollector();
            $employeeEmergencyContact = $employeeCollector->CollectEmergencyContact($req, $employeeId, $contactId);
            $employeeRepo = new Repos\EmployeeRepo();
            $employeeRepo->addEmergencyContact($employeeEmergencyContact);
        }
        //End Contact
        $contactCollector->ProcessPhoneNumbersForContact($req, $contactId);
        $contactCollector->ProcessEmailAddressesForContact($req, $contactId);
        $contactCollector->ProcessAddressForContact($req, $contactId);

        DB::commit();
        return response()->json([
            'success' => true,
            'emergency_contacts' => $employeeRepo->getEmergencyContacts($employee->employee_id)
        ]);
    }

    public function toggleActive(Request $req, $employeeId) {
        $employeeRepo = new Repos\EmployeeRepo();
        $employee = $employeeRepo->GetById($employeeId, null);
        if($req->user()->cannot('updateAdvanced', $employee))
            abort(403);

        DB::beginTransaction();

        $employeeRepo->toggleActive($employeeId);

        DB::commit();
        return response()->json([
            'success' => true
        ]);
    }
}
