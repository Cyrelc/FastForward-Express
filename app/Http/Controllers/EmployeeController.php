<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Employee;
use \App\Http\Validation\Utils;

class EmployeeController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        $factory = new Employee\EmployeeModelFactory();
        $contents = $factory->ListAll();

        return view('employees.employees', compact('contents'));
    }

    public function create(Request $req) {
        $factory = new Employee\EmployeeModelFactory();
        $model = $factory->GetCreateModel($req);

        return view('employees.employee', compact('model'));
    }

    public function edit(Request $req, $id) {
        $factory = new Employee\EmployeeModelFactory();
        $model = $factory->GetEditModel($req, $id);

        return view('employees.employee', compact('model'));
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try{
            $employeeRules = (new \App\Http\Validation\EmployeeValidationRules())->GetValidationRules($req);
            $partialsRules = new \App\Http\Validation\PartialsValidationRules();

            $contactValidator = new \App\Http\Validation\PartialsValidationRules();

            $contactCollector = new \App\Http\Collectors\ContactCollector();
            $contactsCollector = new \App\Http\Collectors\ContactsCollector();
            $addressCollector = new \App\Http\Collectors\AddressCollector();

            $emergencyContacts = $contactsCollector->collectAll($req, 'emergency-contact', true);
            $employeeContact = $contactCollector->Collect($req, 'employee');
            $employeeContact['phone_numbers'] = $contactCollector->CollectPhoneNumbers($req, 'employee');
            $employeeContact['emails'] = $contactCollector->CollectEmails($req, 'employee');
            $employeeContact['address'] = $addressCollector->Collect($req, 'employee', true);

            $employeeId = $req->input('employee_id');
            $isEdit = $employeeId !== null && $employeeId > 0;

            $validationRules = [];
            $validationMessages = [];

            $validationRules = array_merge($validationRules, $employeeRules['rules']);
            $validationMessages = array_merge($validationMessages, $employeeRules['messages']);

            $contactRules = $contactValidator->GetContactValidationRules($employeeContact, 'Employee');
            $validationRules = array_merge($validationRules, $contactRules['rules']);
            $validationMessages = array_merge($validationMessages, $contactRules['messages']);

            $contactsVal = $partialsRules->GetContactsValidationRules($req, $emergencyContacts, true);
            $validationRules = array_merge($validationRules, $contactsVal['rules']);
            $validationMessages = array_merge($validationMessages, $contactsVal['messages']);

            if (count($emergencyContacts) < 1) {
                //Manually fail validation
                $rules['Contacts'] = 'required';
                $validator =  \Illuminate\Support\Facades\Validator::make($req->all(), $rules);
                if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();
            }

            if ($req->is_driver == 'true') {
                $driverValidation = (new \App\Http\Validation\DriverValidationRules())->GetValidationRules($req);
                $validationRules = array_merge($validationRules, $driverValidation['rules']);
                $validationMessage = array_merge($validationMessages, $driverValidation['messages']);
            }

            $this->validate($req, $validationRules, $validationMessages);

            $userRepo = new Repos\UserRepo();
            $phoneNumberRepo = new Repos\PhoneNumberRepo();
            $emailAddressRepo = new Repos\EmailAddressRepo();
            $addressRepo = new Repos\AddressRepo();
            $contactRepo = new Repos\ContactRepo();
            $employeeRepo = new Repos\EmployeeRepo();
            if ($req->is_driver) {
                $driverRepo = new Repos\DriverRepo();
            }

            $userCollector = new \App\Http\Collectors\UserCollector();
            $user = $userCollector->CollectEmployee($req, 'employee');

            $user['user_id'] = $req->input('user_id');

            //Contact Info/User
            if($isEdit) {
                $userId = $userRepo->Update($user, ['Employee'])->user_id;
                $contactId = $contactRepo->Update($employeeContact)->contact_id;
            } else {
                $userId = $userRepo->Insert($user, 'Employee')->user_id;
                $contactId = $contactRepo->Insert($employeeContact)->contact_id;
            }

            $employeeContact['address']['contact_id'] = $contactId;
            $employeeCollector = new \App\Http\Collectors\EmployeeCollector();

            $employee = $employeeCollector->Collect($req, $contactId, $userId);
            if ($isEdit) {
                $employeeRepo->Update($employee);
                $addressRepo->Update($employeeContact['address']);
            } else {
                $addressRepo->Insert($employeeContact['address']);
                $employeeId = $employeeRepo->Insert($employee)->employee_id;
            }
            foreach($employeeContact['emails'] as $email) {
                $email['contact_id'] = $contactId;
                if(isset($email['email_address_id']))
                    $emailAddressRepo->Update($email);
                else
                    $emailAddressRepo->Insert($email);
            }
            foreach($employeeContact['phone_numbers'] as $phone)
                    $phoneNumberRepo->Handle($phone, $contactId);

            if ($req->is_driver == 'true') {
                dd("whaaaaaat?!?!");
                $driverCollector = new \App\Http\Collectors\DriverCollector();
                $driver_data = $driverCollector->Collect($req, (string)$employeeId);
                if ($req->driver_id != null) {
                    $driverRepo->Update($driver_data);
                } else {
                    $driverRepo->Insert($driver_data);
                }
            } else {
                if($driverRepo->GetByEmployeeId($employeeId) != null)
                    $driverRepo->DeleteByEmployeeId($employeeId);
            }

            //BEGIN emergency contacts
            $newPrimaryId = $req->input('emergency-contact-current-primary');
            $emergencyContactIds = $contactRepo->HandleEmergencyContacts($emergencyContacts, $employeeId, $newPrimaryId);
            //END emergency contacts

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
