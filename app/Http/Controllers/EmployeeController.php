<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Employee;
use \App\Http\Validation\Utils;

class EmployeeController extends Controller {

    public function buildTable() {
        $employeeRepo = new Repos\EmployeeRepo();
        return $employeeRepo->ListAll();
    }

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        return view('employees.employees');
    }

    public function create(Request $req) {
        $factory = new Employee\EmployeeModelFactory();
        $model = $factory->GetCreateModel($req);
        return view('employees.employee', compact('model'));
    }

    public function createEmergencyContact(Request $req, $employee_id) {
        $modelFactory = new \App\Http\Models\Partials\ContactModelFactory();
        $model = $modelFactory->GetCreateModel();
        $model->employee_id = $employee_id;
        $model->emails->types = null;
        return view('employees.editEmergencyContact', compact('model'));
    }

    public function edit(Request $req, $id) {
        $factory = new Employee\EmployeeModelFactory();
        $model = $factory->GetEditModel($req, $id);

        return view('employees.employee', compact('model'));
    }

    public function editEmergencyContact(Request $req, $id) {
        $modelFactory = new \App\Http\Models\Partials\ContactModelFactory();
        $model = $modelFactory->GetEditModel($id, true);
        $model->emails->types = null;
        return view('employees.editEmergencyContact', compact('model'));
    }

    public function storeEmergencyContact(Request $req) {
        DB::beginTransaction();
        try {
            $partialsValidation = new \App\Http\Validation\PartialsValidationRules();

            $temp = $partialsValidation->GetContactValidationRules($req, true, true, true);

            $this->validate($req, $temp['rules'], $temp['messages']);

            $contactCollector = new \App\Http\Collectors\ContactCollector();

            //Begin Contact
            $contactId = $req->contact_id;
            $contactRepo = new Repos\ContactRepo();
            $contact = $contactCollector->GetContact($req);
            if($contactId == '') {
                $contactId = $contactRepo->Insert($contact)->contact_id;
                $employeeCollector = new \App\Http\Collectors\EmployeeCollector();
                $employeeEmergencyContact = $employeeCollector->CollectEmergencyContact($req, $contactId);
                $employeeRepo = new Repos\EmployeeRepo();
                $employeeRepo->AddEmergencyContact($employeeEmergencyContact);
            }
            else
                $contactRepo->Update($contact);
            //End Contact
            $contactCollector->ProcessPhonesForContact($req, $contactId);
            $contactCollector->ProcessEmailsForContact($req, $contactId);

            DB::commit();
        } catch (exception $e) {
            DB::rollBack();

            return response()->json([
                'success'=> false,
                'error'=>$e->getMessage()
            ]);
        }
    }

    public function getEmergencyContactsTable(Request $req, $id) {
        $employeeModelFactory = new Employee\EmployeeModelFactory();
        $model = $employeeModelFactory->ListEmergencyContacts($id);
        return json_encode($model);
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try{
            $employeeRules = (new \App\Http\Validation\EmployeeValidationRules())->GetValidationRules($req);
            $partialsRules = new \App\Http\Validation\PartialsValidationRules();

            $contactValidator = new \App\Http\Validation\PartialsValidationRules();

            $contactCollector = new \App\Http\Collectors\ContactCollector();
            $addressCollector = new \App\Http\Collectors\AddressCollector();

            $contact = $contactCollector->GetContact($req);
            $address = $addressCollector->Collect($req, 'contact', true);

            $employeeId = $req->input('employee_id');
            $isEdit = $employeeId !== null && $employeeId > 0;

            $validationRules = [];
            $validationMessages = [];

            $validationRules = array_merge($validationRules, $employeeRules['rules']);
            $validationMessages = array_merge($validationMessages, $employeeRules['messages']);

            $contactRules = $contactValidator->GetContactValidationRules($req, true, true, true);
            $validationRules = array_merge($validationRules, $contactRules['rules']);
            $validationMessages = array_merge($validationMessages, $contactRules['messages']);

            if ($req->is_driver == 'on') {
                $driverValidation = (new \App\Http\Validation\DriverValidationRules())->GetValidationRules($req);
                $validationRules = array_merge($validationRules, $driverValidation['rules']);
                $validationMessage = array_merge($validationMessages, $driverValidation['messages']);
            }

            $this->validate($req, $validationRules, $validationMessages);

            $userRepo = new Repos\UserRepo();
            $addressRepo = new Repos\AddressRepo();
            $contactRepo = new Repos\ContactRepo();
            $employeeRepo = new Repos\EmployeeRepo();
            if ($req->is_driver) {
                $driverRepo = new Repos\DriverRepo();
            }

            $userCollector = new \App\Http\Collectors\UserCollector();
            $user = $userCollector->Collect($req);

            //Begin User
            if($isEdit) {
                $user['user_id'] = $userRepo->GetUserByEmployeeId($employeeId)->user_id;
                $userId = $userRepo->Update($user)->user_id;
                $contactId = $contactRepo->Update($contact)->contact_id;
                $addressRepo->Update($address);
            } else {
                $userId = $userRepo->Insert($user)->user_id;
                $contactId = $contactRepo->Insert($contact)->contact_id;
                $address['contact_id'] = $contactId;
                $addressRepo->Insert($address);
            }
            $contactCollector->ProcessEmailsForContact($req, $contactId);
            $contactCollector->ProcessPhonesForContact($req, $contactId);
            
            $employeeCollector = new \App\Http\Collectors\EmployeeCollector();

            $employee = $employeeCollector->Collect($req, $contactId, $userId);
            if ($isEdit) {
                $employeeRepo->Update($employee);
            } else {
                $employeeId = $employeeRepo->Insert($employee)->employee_id;
            }

            if ($req->is_driver == 'on') {
                $driverCollector = new \App\Http\Collectors\DriverCollector();
                $driver_data = $driverCollector->Collect($req, (string)$employeeId);
                if ($req->driver_id != null) {
                    $driverRepo->Update($driver_data);
                } else {
                    $driverRepo->Insert($driver_data);
                }
            } //else {
                //if($driverRepo->GetByEmployeeId($employeeId) != null)
                    //$driverRepo->DeleteByEmployeeId($employeeId);
                    //TODO: Unable to delete if there are chargebacks present. Check for this.
                    //Fix - don't delete. Only ever disable.
            // }

            DB::commit();

            if ($isEdit)
                return redirect()->action('EmployeeController@edit',['employee_id'=>$employee['employee_id']]);
            else
                return redirect()->action('EmployeeController@create');

        } catch(Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function action (Request $req) {
        try {
            $id = $req->input('id');
            if (!isset($id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'ID was not specified.'
                ]);
            }

            $employeeRepo = new Repos\EmployeeRepo();

            $employee = $employeeRepo->GetById($id);

            if ($req->input('action') == 'deactivate')
                $employee->active = false;
            else if ($req->input('action') == 'activate')
                $employee->active = true;

            $employeeRepo->Update($employee);

            return response()->json([
                'success' => true
            ]);
        } catch(Exception $e){
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function genFilterData($input) {
        return null;
    }
}
