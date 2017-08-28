<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Repos;
use App\Http\Models\Driver;
use \App\Http\Validation\Utils;

class DriverController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        $factory = new Driver\DriverModelFactory();
        $contents = $factory->ListAll();

        return view('drivers.drivers', compact('contents'));
    }

    public function create(Request $req) {
        $factory = new Driver\DriverModelFactory();
        $model = $factory->GetCreateModel($req);

        return view('drivers.driver', compact('model'));
    }

    public function edit(Request $req, $id) {
        $factory = new Driver\DriverModelFactory();
        $model = $factory->GetEditModel($req, $id);

        return view('drivers.driver', compact('model'));
    }

    public function store(Request $req) {
        $driverRules = (new \App\Http\Validation\DriverValidationRules())->GetValidationRules();
        $partialsRules = new \App\Http\Validation\PartialsValidationRules();

        $contactValidator = new \App\Http\Validation\PartialsValidationRules();

        $contactCollector = new \App\Http\Collectors\ContactCollector();
        $contactsCollector = new \App\Http\Collectors\ContactsCollector();

        $driverId = $req->input('driver-id');
        $isEdit = $driverId !== null && $driverId > 0;

        $validationRules = [];
        $validationMessages = [];

        $validationRules = array_merge($validationRules, $driverRules['rules']);
        $validationMessages = array_merge($validationMessages, $driverRules['messages']);

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

        $this->validate($req, $validationRules, $validationMessages);

        $userCollector = new \App\Http\Collectors\UserCollector();
        $addressCollector = new \App\Http\Collectors\AddressCollector();

        $userRepo = new Repos\UserRepo();
        $phoneNumberRepo = new Repos\PhoneNumberRepo();
        $emailAddressRepo = new Repos\EmailAddressRepo();
        $addressRepo = new Repos\AddressRepo();
        $contactRepo = new Repos\ContactRepo();
        $driverRepo = new Repos\DriverRepo();

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
                        $driverRepo->AddContact($driverId, $newId);
                    } else {
                        if ($newPrimaryId == $contactId)
                            $newPrimaryId = $newId;
                    }

                    array_push($emergencyContactIds, $contactId);
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

                    if ($req->input('pn-action-add-' . $contactId) != null)
                        $phoneNumberRepo->Insert($phone2);
                    if ($req->input('em-action-add-' . $contactId) != null && $req->input('primary-email2') != null)
                        $emailAddressRepo->Insert($email2);

                    //Existing phone numbers on existing account
                    if ($req->input('contact-' . $contactId . '-phone2-id') != null)
                        $phoneNumberRepo->Update($phone2);
                    if ($req->input('contact-' . $contactId . '-email2-id') != null && $req->input('primary-email2') != null)
                        $emailAddressRepo->Update($email2);
                }
            }
        }

        //Handle deletes of all secondary emails/pn's together
        $phoneNumbersToDelete = $req->input('pn-action-delete');
        $emailsToDelete = $req->input('em-action-delete');

        if ($phoneNumbersToDelete !== null) {
            if(is_array($phoneNumbersToDelete))
                foreach($phoneNumbersToDelete as $phoneNumber)
                    $phoneNumberRepo->Delete($phoneNumber);
            else
                $phoneNumberRepo->Delete($phoneNumbersToDelete);
        }

        if ($emailsToDelete !== null) {
            if(is_array($emailsToDelete))
                foreach($emailsToDelete as $email)
                    $phoneNumberRepo->Delete($email);
            else
                $phoneNumberRepo->Delete($emailsToDelete);
        }

        if ($contactsToDelete !== null)
            foreach($contactsToDelete as $delete_id)
                $contactRepo->Delete($delete_id);
        //END contacts

        $user = $userCollector->CollectDriver($req, 'contact');
        $contact = $contactCollector->Collect($req, 'contact');
        $contact['contact_id'] = $req->input('id-for-contact');
        $user['user_id'] = $req->input('user-id');

        //Contact Info/User
        if($isEdit) {
            $userId = $userRepo->Update($user, ['Driver'])->user_id;
            $contactId = $contactRepo->Update($contact)->contact_id;
        } else {
            $userId = $userRepo->Insert($user, 'Driver')->user_id;
            $contactId = $contactRepo->Insert($contact)->contact_id;
        }

        $driverCollector = new \App\Http\Collectors\DriverCollector();

        $phone1 = $contactCollector->CollectPhoneNumberSingle($req, 'contact-phone1', $contactId, true);
        $phone2 = $contactCollector->CollectPhoneNumberSingle($req, 'contact-phone2', $contactId, false);
        $email1 = $contactCollector->CollectEmailSingle($req, 'contact-email1', $contactId, true);
        $email2 = $contactCollector->CollectEmailSingle($req, 'contact-email2', $contactId, false);
        $pager = $driverCollector->CollectPager($req, $contactId);
        $address = $addressCollector->Collect($req, 'no-contact', true, $contactId);
        $address['contact_id'] = $contactId;
        $driver = $driverCollector->Collect($req, $contactId, $userId);

        if ($isEdit) {
            $driverRepo->Update($driver);
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
            if ($req->input('contact-' . $contactId . '-pager-id') != null)
                $phoneNumberRepo->Update($pager);
        } else {
            $phoneNumberRepo->Insert($phone1);
            $emailAddressRepo->Insert($email1);
            $addressRepo->Insert($address);

            if ($phone2['phone_number'] !== null && strlen($phone2['phone_number']) > 0)
                $phoneNumberRepo->Insert($phone2);

            if ($pager['phone_number'] !== null && strlen($pager['phone_number']) > 0)
                $phoneNumberRepo->Insert($pager);

            if ($email2['email'] !== null && strlen($email2['email']) > 0)
                $emailAddressRepo->Insert($email2);
            
                $driverId = $driverRepo->Insert($driver, $emergencyContactIds)->driver_id;
        }

        //Handle change of primary
        if ($newPrimaryId != null) {
            if (Utils::HasValue($driverId)) {
                $driverRepo->ChangePrimary($driverId, $newPrimaryId);
            }
        }

        if ($isEdit)
            return redirect()->action('DriverController@edit',['driver_id'=>$driver['driver_id']]);
        else {
            return redirect()->action('DriverController@create');
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

            $driverRepo = new Repos\DriverRepo();

            $driver = $driverRepo->GetById($id);

            if ($req->input('action') == 'deactivate')
                $driver->active = false;
            else if ($req->input('action') == 'activate')
                $driver->active = true;

            $driverRepo->Update($driver);

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
