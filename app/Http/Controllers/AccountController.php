<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Account;

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
        return view('accounts.account', compact('model'));
    }

    public function store(Request $req) {
        $partialsRules = new \App\Http\Validation\PartialsValidationRules();
        $accountsRules = new \App\Http\Validation\AccountValidationRules();

        $acctRules = $accountsRules->GetValidationRules($req->input('account-number') !== null && $req->input('account-id') !== null && $req->input('account-id') > 0,
            $req->input('account-id'), $req->input('account-number'), $req->input('isSubLocation') == 'true', $req->input('shouldGiveDiscount') == 'true',
            $req->input('useCustomField') == 'true');

        $validationRules = $acctRules['rules'];
        $validationMessages = $acctRules['messages'];

        $addrRules = $partialsRules->GetAddressValidationRules('delivery', 'Delivery');
        $validationRules = array_merge($validationRules, $addrRules['rules']);
        $validationMessages = array_merge($validationMessages, $addrRules['messages']);

        $contacts = 0;
        $contactsToDelete = [];
        foreach($req->all() as $key=>$value) {
            if (substr($key, 0,15)  == "contact-delete-") {
                array_push($contactsToDelete, $req->input($key));
            }
        }

        foreach($req->all() as $key=>$value) {
            if (substr($key, 0, 11) == "contact-id-") {
                $contactId = substr($key, 11);

                //Skip validation for any deleted contacts, and don't increase the contact count
                if (in_array($contactId, $contactsToDelete))
                    continue;

                $contacts++;
                $fName = $req->input('contact-' . $contactId . '-first-name');
                $lName = $req->input('contact-' . $contactId . '-last-name');

                $contactValidationRules = $partialsRules->GetContactValidationRules('contact-' . $contactId, 'Contact ' . $fName . ' ' . $lName);
                $validationRules = array_merge($validationRules, $contactValidationRules['rules']);
                $validationMessages = array_merge($validationMessages, $contactValidationRules['messages']);
            }
        }

        if ($contacts == 0) {
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
        $pnRepo = new Repos\PhoneNumberRepo();
        $comRepo = new Repos\CommissionRepo();

        //Create array of all actions to be taken with contacts
        $secondary_ids = array();
        $contactActions = [];
        foreach($req->all() as $key=>$value) {
            if (substr($key, 0, 15) == "contact-action-") {
                $ids = $req->input($key);
                $type = substr($key, 15);

                if (!is_array($ids))
                    $ids = [$ids];

                foreach ($ids as $contactId) {
                    if (array_key_exists($contactId, $contactActions))
                        array_push($contactActions[$contactId], $type);
                    else
                        $contactActions[$contactId] = [$type];
                }
            }
        }

        //BEGIN contacts
        $primary_id = null;
        $contactCollector = new \App\Http\Collectors\ContactCollector();
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
                    $isAnotherPrimary = false;
                    foreach($contactActions as $actions) {
                        foreach($actions as $action) {
                            if ($action == "change-primary")
                                $isAnotherPrimary = true;
                        }
                    }

                    if (!$isAnotherPrimary) {
                        //Manually fail validation
                        $rules['PrimaryContact'] = 'required';
                        $validator =  \Illuminate\Support\Facades\Validator::make($req->all(), $rules);
                        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();
                    }

                    $contactRepo->Delete($contactId);
                    continue;
                }

                $contact = $contactCollector->Collect($req, 'contact-' , $contactId);

                if ($primaryAction == "new") {
                    $primary_id = $contactRepo->Insert($contact)->contact_id;
                    $contactId = $primary_id;
                }
                else if ($primaryAction == "update") {
                    $contact["contact_id"] = $contactId;
                    $contactRepo->Update($contact);
                }

                $phone1 = $contactCollector->CollectPhoneNumber($req, 'contact-' . $contactId . '-phone1', true, $contactId);
                $phone2 = $contactCollector->CollectPhoneNumber($req, 'contact-' . $contactId, false, $contactId);
                $email1 = $contactCollector->CollectEmail($req, 'contact-' . $contactId, true, $contactId);
                $email2 = $email1 = $contactCollector->CollectEmail($req, 'contact-' . $contactId, false, $contactId);

                if ($primaryAction == "new") {
                    //New phone numbers on new account
                    $pnRepo->Insert($phone1);
                    $emailAddressRepo->Insert($email1);
                } else if ($primaryAction == "update") {
                    //New phone numbers on existing account
                    $pnRepo->Update($phone1);
                    $emailAddressRepo->Update($email1);

                    if ($req->input('pn-action-add-' . $contactId) != null)
                        $pnRepo->Insert($phone2);
                    if ($req->input('em-action-add-' . $contactId) != null && $req->input('primary-email2') != null)
                        $emailAddressRepo->Insert($email2);

                    //Existing phone numbers on existing account
                    if ($req->input('contact-' . $contactId . '-phone2-id') != null)
                        $pnRepo->Update($phone2);
                    if ($req->input('contact-' . $contactId . '-email2-id') != null && $req->input('primary-email2') != null)
                            $emailAddressRepo->Update($email2);
                }
            }
        }

        //Handle deletes of all secondary emails/pn's together
        $pnsToDelete = $req->input('pn-action-delete');
        $emsToDelete = $req->input('em-action-delete');

        if ($pnsToDelete !== null) {
            if(is_array($pnsToDelete))
                foreach($pnsToDelete as $pn)
                    $pnRepo->Delete($pn);
            else
                $pnRepo->Delete($pnsToDelete);
        }

        if ($emsToDelete !== null) {
            if(is_array($emsToDelete))
                foreach($emsToDelete as $em)
                    $pnRepo->Delete($em);
            else
                $pnRepo->Delete($emsToDelete);
        }
        //END contacts

        //BEGIN delivery address
        $addrCollector = new \App\Http\Collectors\AddressCollector();
        $delivery = $addrCollector->Collect($req, 'delivery', true);
        $delivery_id = $delivery["address_id"];

        if ($delivery_id == null)
            $delivery_id = $addressRepo->Insert($delivery)->address_id;
        else
            $addressRepo->Update($delivery);

        //END delivery address
        //BEGIN billing address
        $billing_id = $req->input('billing-id');
        if ($req->input('billing-address') == 'on') {
            $billing = $addrCollector->Collect($req, 'billing', false);

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

        $accountId = $req->input('account-id');
		$isNew = $accountId == null;
		$args = [];
        if ($isNew) {
            $accountId = $accountRepo->Insert($account, $primary_id, $secondary_ids)->account_id;
            $action = 'AccountController@create';
        }
        else {
            $account["account_id"] = $accountId;
            $accountRepo->Update($account);
            $action = 'AccountController@edit';
            $args = ['id' => $accountId];
        }

        //Handle change of primary
        $newPrimaryId = $req->input('contact-action-change-primary');
        if ($newPrimaryId != null)
            $accountRepo->ChangePrimary($accountId, $newPrimaryId);

        //Commission
        $commissionCollector = new \App\Http\Collectors\CommissionCollector();
        $commission1 = $commission2 = null;

        if ($req->input('give-commission-1') == 'true')
            $commission1 = $commissionCollector->Collect($req, 'commission-1', $accountId);

        if ($req->input('give-commission-2') == 'true')
            $commission2 = $commissionCollector->Collect($req, 'commission-2', $accountId);

        if ($isNew) {
            if ($commission1 !== null)
                $comRepo->Insert($commission1);

            if ($commission2 !== null)
                $comRepo->Insert($commission2);
        } else {
            if ($req->input('give-commission-1') == 'true') {
                if ($commission1->commission_id == null)
                    $comRepo->Insert($commission1);
                else
                    $comRepo->Update($commission1);
            } else if ($commission1 != null)
                $comRepo->Delete($commission1->commission_id);

            if ($req->input('give-commission-2') == 'true') {
                if ($commission2->commission_id == null)
                    $comRepo->Insert($commission2);
                else
                    $comRepo->Update($commission2);
            } else if ($commission2 != null)
                $comRepo->Delete($commission2->commission_id);
        }

        //END account

        return redirect()->action($action, $args);
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
