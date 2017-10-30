<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Account;
use \App\Http\Validation\Utils;


class AccountController extends Controller {

    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'name';
        $this->maxCount = env('DEFAULT_CUSTOMER_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_CUSTOMER_AGE', '6 month');
        $this->class = new \App\Account;
    }

    public function index() {
        $factory = new Account\AccountModelFactory();
        $contents = $factory->ListAll();

        return view('accounts.accounts', compact('contents'));
    }

    public function create(Request $req) {
        //Check user settings and return popout or inline based on that
        //Check permissions
        $amf = new Account\AccountModelFactory();
        $model = $amf->GetCreateModel($req);
        return view('accounts.account', compact('model'));
    }

    public function edit(Request $req, $id) {
        $factory = new Account\AccountModelFactory();
        $model = $factory->GetEditModel($id, $req);
        //dd($model->account->contacts);
        return view('accounts.account', compact('model'));
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try {
            $partialsRules = new \App\Http\Validation\PartialsValidationRules();
            $accountsRules = new \App\Http\Validation\AccountValidationRules();
            $contactCollector = new \App\Http\Collectors\ContactCollector();
            $contactsCollector = new \App\Http\Collectors\ContactsCollector();

            $acctRules = $accountsRules->GetValidationRules($req->input('account-number') !== null && $req->input('account-id') !== null && $req->input('account-id') > 0,
                $req->input('account-id'), $req->input('account-number'), $req->input('isSubLocation') == 'true', $req->input('shouldGiveDiscount') == 'true',
                $req->input('useCustomField') == 'true');

            $validationRules = $acctRules['rules'];
            $validationMessages = $acctRules['messages'];

            $addrRules = $partialsRules->GetAddressValidationRules('delivery', 'Delivery');
            $validationRules = array_merge($validationRules, $addrRules['rules']);
            $validationMessages = array_merge($validationMessages, $addrRules['messages']);

            $contactsToDelete = $contactsCollector->GetDeletions($req);

            $contactsVal = $partialsRules->GetContactsValidationRules($req, $contactsToDelete, false);
            $validationRules = array_merge($validationRules, $contactsVal['rules']);
            $validationMessages = array_merge($validationMessages, $contactsVal['messages']);

            if ($contactsVal['contact_count'] == 0) {
                //Manually fail validation
                $rules['Contacts'] = 'required';
                $validator =  \Illuminate\Support\Facades\Validator::make($req->all(), $rules);
                if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();
            }

            if ($req->input('billing-address') == 'on') {
                $billAddressRules = $partialsRules->GetAddressValidationRules('billing', 'Billing');
                $validationRules = array_merge($validationRules, $billAddressRules['rules']);
                $validationMessages = array_merge($validationMessages, $billAddressRules['messages']);
            }

            for ($i = 1; $i < 3; $i++) {
                if ($req->input('give-commission-' . $i) == 'true') {
                    $valRules = $partialsRules->GetCommissionValidationRules('commission-' . $i, 'Commission', ($req->input('depreciate-' . $i . '-percent') != '' || $req->input('depreciate-' . $i . '-duration') != '' || $req->input('depreciate-' . $i . '-start-date') != ''));
                    $validationRules = array_merge($validationRules, $valRules['rules']);
                    $validationMessages = array_merge($validationMessages, $valRules['messages']);
                }
            }

            $this->validate($req, $validationRules, $validationMessages);

            $accountRepo = new Repos\AccountRepo();
            $contactRepo = new Repos\ContactRepo();
            $addressRepo = new Repos\AddressRepo();
            $emailAddressRepo = new Repos\EmailAddressRepo();
            $phoneNumberRepo = new Repos\PhoneNumberRepo();
            $commissionRepo = new Repos\CommissionRepo();

            //Create array of all actions to be taken with contacts
            $contactIds = array();
            $contactActions = $contactsCollector->GetActions($req);

            $accountId = $req->input('account-id');
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

                        if (Utils::HasValue($accountId)) {
                            $accountRepo->AddContact($accountId, $newId);
                        } else {
                            if ($newPrimaryId == $contactId)
                                $newPrimaryId = $newId;
                        }

                        array_push($contactIds, $newId);
                    }
                    else if ($primaryAction == "update") {
                        $contact["contact_id"] = $contactId;
                        $contactRepo->Update($contact);
                    }

                    $phone1 = $contactCollector->CollectPhoneNumber($req, $contactId, true, $newId);
                    $phone2 = $contactCollector->CollectPhoneNumber($req, $contactId, false, $newId);
                    $email1 = $contactCollector->CollectEmail($req, $contactId, true, $newId);
                    $email2 = $contactCollector->CollectEmail($req, $contactId, false, $newId);

                    if (isset($newId))
                        $contactId = $newId;

                    if ($primaryAction == "new") {
                        //New phone numbers on new account
                        $phoneNumberRepo->Insert($phone1);
                        $emailAddressRepo->Insert($email1);

                        if (Utils::HasValue($phone2['phone_number']))
                            $phoneNumberRepo->Insert($phone2);

                        if (Utils::HasValue($email2['email']))
                            $emailAddressRepo->Insert($email2);
                    } else if ($primaryAction == "update") {
                        //New phone numbers on existing account
                        $phoneNumberRepo->Update($phone1);
                        $emailAddressRepo->Update($email1);

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

            //BEGIN delivery address
            $addrCollector = new \App\Http\Collectors\AddressCollector();
            $delivery = $addrCollector->CollectForAccount($req, 'delivery');
            $delivery_id = $delivery["address_id"];

            if ($delivery_id == null)
                $delivery_id = $addressRepo->Insert($delivery)->address_id;
            else
                $addressRepo->Update($delivery);

            //END delivery address
            //BEGIN billing address
            $billing_id = $req->input('billing-id');
            if ($req->input('billing-address') == 'on') {
                $billing = $addrCollector->CollectForAccount($req, 'billing');

                if ($billing_id == null || $billing_id == '')
                    $billing_id = $addressRepo->Insert($billing)->address_id;
                else
                    $addressRepo->Update($billing);
            } else if ($billing_id != null || $billing_id != '')
                    $addressRepo->Delete($billing_id);

            if ($billing_id == '')
                $billing_id = null;
            //END billing address
            //BEGIN account
            $acctCollector = new \App\Http\Collectors\AccountCollector();
            $account = $acctCollector->Collect($req, $billing_id, $delivery_id);

    		$isNew = $accountId == null;
    		$args = [];
            if ($isNew) {
                $accountId = $accountRepo->Insert($account, $contactIds)->account_id;
                $action = 'AccountController@create';
            }
            else {
                $account["account_id"] = $accountId;
                $accountRepo->Update($account);
                $action = 'AccountController@edit';
                $args = ['id' => $accountId];
            }

            //Handle change of primary
            if ($newPrimaryId != null) {
                if (Utils::HasValue($accountId)) {
                    $accountRepo->ChangePrimary($accountId, $newPrimaryId);
                }
            }
            //Commission
            $commissionCollector = new \App\Http\Collectors\CommissionCollector();
            $commission1 = $commission2 = null;

            if ($req->input('give-commission-1') == 'true')
                $commission1 = $commissionCollector->Collect($req, 'commission-1', $accountId);

            if ($req->input('give-commission-2') == 'true')
                $commission2 = $commissionCollector->Collect($req, 'commission-2', $accountId);

            if ($isNew) {
                if ($commission1 !== null)
                    $commissionRepo->Insert($commission1);

                if ($commission2 !== null)
                    $commissionRepo->Insert($commission2);
            } else {
                if ($req->input('give-commission-1') == 'true') {
                    if ($commission1->commission_id == null)
                        $commissionRepo->Insert($commission1);
                    else
                        $commissionRepo->Update($commission1);
                } else if ($commission1 != null)
                    $commissionRepo->Delete($commission1->commission_id);

                if ($req->input('give-commission-2') == 'true') {
                    if ($commission2->commission_id == null)
                        $commissionRepo->Insert($commission2);
                    else
                        $commissionRepo->Update($commission2);
                } else if ($commission2 != null)
                    $commissionRepo->Delete($commission2->commission_id);
            }

            DB::commit();
            //END account

            return redirect()->action($action, $args);

        } catch(Exception $e) {
            DB::rollBack();
            dd(response());
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

            $acctRepo = new Repos\AccountRepo();

            $acct = $acctRepo->GetById($id);

            if ($req->input('action') == 'deactivate')
                $acct->active = false;
            else if ($req->input('action') == 'activate')
                $acct->active = true;

            $acctRepo->Edit($acct);

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

    public function is_unique(Request $req) {
        try {
            $number = $req->input('number');
            if (!isset($number) || strlen($number) <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Account Number was not specified.'
                ]);
            }

            $acctRepo = new Repos\AccountRepo();
            $unique = $acctRepo->IsUnique($number);

            return response()->json([
                'success' => true,
                'accounts' => $unique
            ]);
        } catch(Exception $e){
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
