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

            $employeeId = $req->input('employee_id');
            $isEdit = $employeeId !== null && $employeeId > 0;

            $validationRules = [];
            $validationMessages = [];

            $validationRules = array_merge($validationRules, $employeeRules['rules']);
            $validationMessages = array_merge($validationMessages, $employeeRules['messages']);

            $contactRules = $contactValidator->GetContactValidationRules('contact', 'Contact');
            $validationRules = array_merge($validationRules, $contactRules['rules']);
            $validationMessages = array_merge($validationMessages, $contactRules['messages']);

            $contactsToDelete = $contactsCollector->GetDeletions($req);

            $contactsVal = $partialsRules->GetContactsValidationRules($req, $contactsToDelete, true);
            $validationRules = array_merge($validationRules, $contactsVal['rules']);
            $validationMessages = array_merge($validationMessages, $contactsVal['messages']);

            if ($contactsVal['contact_count'] < 1) {
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

            $userCollector = new \App\Http\Collectors\UserCollector();
            $addressCollector = new \App\Http\Collectors\AddressCollector();

            $userRepo = new Repos\UserRepo();
            $phoneNumberRepo = new Repos\PhoneNumberRepo();
            $emailAddressRepo = new Repos\EmailAddressRepo();
            $addressRepo = new Repos\AddressRepo();
            $contactRepo = new Repos\ContactRepo();
            $employeeRepo = new Repos\EmployeeRepo();
            if ($req->is_driver) {
                $driverRepo = new Repos\DriverRepo();
            }

            $emergencyContactIds = array();        
            $contactActions = $contactsCollector->GetActions($req);
            
            //Emergency Contact
            //BEGIN contacts
            $primary_id = null;
            $newPrimaryId = $req->input('contact-action-change-primary');

            foreach($req->all() as $key=>$value) {
                if (substr($key, 0, 11) == "contact-id-") {
                    $contactId = substr($key, 11);

                    $actions = $contactActions[$contactId];

                    $primaryAction = "";
                    if (in_array('delete', $actions))
                        $primaryAction = 'delete';
                    else if (in_array('update', $actions))
                        $primaryAction = 'update';
                    else if (in_array('new', $actions))
                        $primaryAction = 'new';

                    //What do we do with this contact? Return fail
                    if ($primaryAction == "") {
                        $rules['Contact-Action'] = 'required';
                        $validator =  \Illuminate\Support\Facades\Validator::make($req->all(), $rules);
                        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();
                    }

                    if ($primaryAction == "delete") {
                        //Deleting contact, delete and don't do anything else

                        //Check that another contact is being added as primary
                        if ($req->input('contact-action-change-primary') === $contactId) {
                            //Manually fail validation
                            $rules['PrimaryContact'] = 'required';
                            $validator =  \Illuminate\Support\Facades\Validator::make($req->all(), $rules);
                            if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();
                        }

                        $contactRepo->Delete($contactId);
                        continue;
                    }

                    $contact = $contactCollector->Collect($req, 'contact-' . $contactId);
                    $newId = null;

                    if ($primaryAction == "new") {
                        $newId = $contactRepo->Insert($contact)->contact_id;

                        if ($isEdit) {
                            $employeeRepo->AddEmergencyContact($employeeId, $newId);
                        } else {
                            if ($newPrimaryId == $contactId)
                                $newPrimaryId = $newId;
                        }

                        array_push($emergencyContactIds, $newId);
                    }
                    else if ($primaryAction == "update") {
                        $contact["contact_id"] = $contactId;
                        $contactRepo->Update($contact);
                    }

                    $phone1 = $contactCollector->CollectPhoneNumber($req, $contactId, true, $newId);
                    $phone2 = $contactCollector->CollectPhoneNumber($req, $contactId, false, $newId);
                    $email1 = $contactCollector->CollectEmail($req, $contactId, true, $newId);
                    $email2 = $contactCollector->CollectEmail($req, $contactId, false, $newId);
                    $address = $addressCollector->Collect($req, $contactId, true, $newId);

                    if (isset($newId))
                        $contactId = $newId;

                    if ($primaryAction == "new") {
                        //New phone numbers on new account
                        $phoneNumberRepo->Insert($phone1);
                        $emailAddressRepo->Insert($email1);
                        $addressRepo->Insert($address);

                        if (Utils::HasValue($phone2['phone_number']))
                            $phoneNumberRepo->Insert($phone2);

                        if (Utils::HasValue($email2['email']))
                            $emailAddressRepo->Insert($email2);
                    } else if ($primaryAction == "update") {
                        //New phone numbers on existing account
                        $phoneNumberRepo->Update($phone1);
                        $emailAddressRepo->Update($email1);
                        $addressRepo->Update($address);

                        if (Utils::HasValue($phone2['phone_number'])) {
                            if (Utils::HasValue($phone2['phone_number_id']))
                                $phoneNumberRepo->Update($phone2);
                            else
                                $phoneNumberRepo->Insert($phone2);
                        } else if (Utils::HasValue($phone2['phone_number_id']))
                            $phoneNumberRepo->Delete($phone2['phone_number_id']);

                        if (Utils::HasValue($email2['email'])) {
                            if (Utils::HasValue($email2['email_address_id']))
                                $emailAddressRepo->Update($email2);
                            else
                                $emailAddressRepo->Insert($email2);
                        } else if (Utils::HasValue($email2['email_address_id']))
                            $emailAddressRepo->Delete($email2['email_address_id']);
                    }
                }
            }

            if ($contactsToDelete !== null)
                foreach($contactsToDelete as $delete_id)
                    $contactRepo->Delete($delete_id);
            //END contacts

            $user = $userCollector->CollectEmployee($req, 'contact');

            $contact = $contactCollector->Collect($req, 'contact');
            $contact['contact_id'] = $req->input('id-for-contact');
            $user['user_id'] = $req->input('user_id');

            //Contact Info/User
            if($isEdit) {
                $userId = $userRepo->Update($user, ['Employee'])->user_id;
                $contactId = $contactRepo->Update($contact)->contact_id;
            } else {
                $userId = $userRepo->Insert($user, 'Employee')->user_id;
                $contactId = $contactRepo->Insert($contact)->contact_id;
            }

            $employeeCollector = new \App\Http\Collectors\EmployeeCollector();

            $phone1 = $contactCollector->CollectPhoneNumberSingle($req, 'contact-phone1', $contactId, true);
            $phone2 = $contactCollector->CollectPhoneNumberSingle($req, 'contact-phone2', $contactId, false);
            $email1 = $contactCollector->CollectEmailSingle($req, 'contact-email1', $contactId, true);
            $email2 = $contactCollector->CollectEmailSingle($req, 'contact-email2', $contactId, false);
            $address = $addressCollector->Collect($req, 'no-contact', true, $contactId);
            $address['contact_id'] = $contactId;
            $employee = $employeeCollector->Collect($req, $contactId, $userId);

            if ($isEdit) {
                $employeeRepo->Update($employee);
                $phoneNumberRepo->Update($phone1);
                $emailAddressRepo->Update($email1);
                $addressRepo->Update($address);

                if ($req->input('pn-action-add-' . $contactId) != null)
                    $phoneNumberRepo->Insert($phone2);
                if ($req->input('em-action-add-' . $contactId) != null && $req->input('primary-email2') != null)
                    $emailAddressRepo->Insert($email2);

                //Existing phone numbers on existing account
                if ($req->input('contact-' . $contactId . '-phone2-id') != null)
                    $phoneNumberRepo->Update($phone2);
                if ($req->input('contact-' . $contactId . '-email2-id') != null && $req->input('primary-email2') != null)
                    $emailAddressRepo->Update($email2);
                // if ($req->input('contact-' . $contactId . '-pager-id') != null)
                //     $phoneNumberRepo->Update($pager);
            } else {
                $phoneNumberRepo->Insert($phone1);
                $emailAddressRepo->Insert($email1);
                $addressRepo->Insert($address);

                if ($phone2['phone_number'] !== null && strlen($phone2['phone_number']) > 0)
                    $phoneNumberRepo->Insert($phone2);

                // if ($pager['phone_number'] !== null && strlen($pager['phone_number']) > 0)
                //     $phoneNumberRepo->Insert($pager);

                if ($email2['email'] !== null && strlen($email2['email']) > 0)
                    $emailAddressRepo->Insert($email2);
                
                    $employeeId = $employeeRepo->Insert($employee, $emergencyContactIds)->employee_id;
            }

            if ($req->is_driver) {
                $driverCollector = new \App\Http\Collectors\DriverCollector();
                $driver_data = $driverCollector->Collect($req, (string)$employeeId);
                if ($req->driver_id != null) {
                    $driverRepo->Update($driver_data);
                } else {
                    $driverRepo->Insert($driver_data);
                }
            } else {
                if($driverRepo->GetByEmployeeId() != null)
                    $driverRepo->DeleteByEmployeeId($employeeId);
            }

            //Handle change of primary
            if ($newPrimaryId != null) {
                if (Utils::HasValue($employeeId)) {
                    $employeeRepo->ChangePrimary($employeeId, $newPrimaryId);
                }
            }

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
