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
        $validationRules = [
            'name' => 'required',
            'delivery-street' => 'required',
            'delivery-city' => 'required',
            //Regex used found here: http://regexlib.com/REDetails.aspx?regexp_id=417
            'delivery-zip-postal' => ['required', 'regex:/^((\d{5}-\d{4})|(\d{5})|([AaBbCcEeGgHhJjKkLlMmNnPpRrSsTtVvXxYy]\d[A-Za-z]\s?\d[A-Za-z]\d))$/'],
            'delivery-state-province' => 'required',
            'delivery-country' => 'required',
        ];

        $validationMessages = [
            'name.required' => 'Company Name is required.',
            'delivery-street.required' => 'Delivery Address Street is required.',
            'delivery-city.required' => 'Delivery Address City is required.',
            'delivery-zip-postal.required' => 'Delivery Address Postal Code is required.',
            'delivery-zip-postal.regex' => 'Delivery Postal Code must be in the format "Q4B 5C5", "501-342", or "123324".',
            'delivery-state-province.required' => 'Delivery Province is required.',
            'delivery-country.required' => 'Delivery Country is required.',
        ];

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

                $validationRules = array_merge($validationRules, [
                    'contact-' . $contactId .'-first-name' => 'required',
                    'contact-' . $contactId . '-last-name' => 'required',
                    //Regex used found here: http://www.regexlib.com/REDetails.aspx?regexp_id=607
                    'contact-' . $contactId . '-phone1' => ['required', 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
                    'contact-' . $contactId . '-phone2' => ['regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
                    'contact-' . $contactId . '-email1' => 'required|email',
                    'contact-' . $contactId .'-email2' => 'email'
                ]);

                $validationMessages = array_merge($validationMessages, [
                    'contact-' . $contactId .'-first-name.required' => 'Secondary Contact First Name is required.',
                    'contact-' . $contactId .'-last-name.required' => 'Secondary Contact Last Name is required.',
                    'contact-' . $contactId .'-phone1.required' => $fName . ' ' . $lName . ' Primary Phone Number is required.',
                    'contact-' . $contactId .'-phone1.regex' => $fName . ' ' . $lName . ' Primary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
                    'contact-' . $contactId .'-phone2.regex' => $fName . ' ' . $lName . ' Secondary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
                    'contact-' . $contactId .'-email1.required' => $fName . ' ' . $lName . ' Primary Email is required.',
                    'contact-' . $contactId .'-email1.email' => $fName . ' ' . $lName . ' Primary Email must be an email.',
                    'contact-' . $contactId .'-email2.email' => $fName . ' ' . $lName . ' Secondary Email must be an email.',
                ]);
            }
        }

        if ($contacts == 0) {
            //Manually fail validation
            $rules['Contacts'] = 'required';
            $validator =  \Illuminate\Support\Facades\Validator::make($req->all(), $rules);
            if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($req->input('billing-address') == 'on') {
            $validationRules = array_merge($validationRules, [
                'billing-street' => 'required',
                'billing-city' => 'required',
                'billing-zip-postal' => ['required', 'regex:/^((\d{5}-\d{4})|(\d{5})|([AaBbCcEeGgHhJjKkLlMmNnPpRrSsTtVvXxYy]\d[A-Za-z]\s?\d[A-Za-z]\d))$/'],
                'billing-state-province' => 'required',
                'billing-country' => 'required'
            ]);

            $validationMessages = array_merge($validationMessages, [
                'billing-street.required' => 'Billing Address Street is required.',
                'billing-city.required' => 'Billing Address City is required.',
                'billing-zip-postal.required' => 'Billing Address Postal Code is required.',
                'billing-zip-postal.regex' => 'Billing Postal Code must be in the format "Q4B 5C5", "501-342", or "123324".',
                'billing-state-province.required' => 'Billing Province is required.',
                'billing-country.required' => 'Billing Country is required.'
            ]);
        }

        if ($req->input('isSubLocation') == 'true') {
            $validationRules = array_merge($validationRules, ['parent-account-id' => 'required']);
            $validationMessages = array_merge($validationMessages, ['parent-account-id.required' => 'A Parent Account is required.']);
        }

        if ($req->input('shouldGiveDiscount') == 'true') {
            $validationRules = array_merge($validationRules, ['discount' => 'required|numeric']);
            $validationMessages = array_merge($validationMessages, [
                'discount.required' => 'A Discount value is required.',
                'discount.numeric' => 'Discount must be a number.'
            ]);
        }

        for ($i = 1; $i < 3; $i++) {
            if ($req->input('give-commission-' . $i) == 'true') {
                $validationRules = array_merge($validationRules, [
                    'commission-' . $i . '-employee-id' => 'required',
                    'commission-' . $i . '-percent' => 'required|numeric']);
                $validationMessages = array_merge($validationMessages, [
                    'commission-' . $i . '-employee-id' => 'A Commission Driver is required.',
                    'commission-' . $i . '-percent.required' => 'A Commission % value is required.',
                    'commission-' . $i . '-percent.numeric' => 'Commission % must be a number.'
                ]);
                if ($req->input('depreciate-' . $i . '-percent') != '' || $req->input('depreciate-' . $i . '-duration') != '' || $req->input('depreciate-' . $i . '-start-date') != '') {
                    $validationRules = array_merge($validationRules,[
                        'depreciate-' . $i . '-percent' => 'required',
                        'depreciate-' . $i . '-duration' => 'required',
                        'depreciate-' . $i . '-start-date' => 'required']);
                    $validationMessages = array_merge($validationMessages, [
                        'depreciate-' . $i . '-percent' => 'Commission depreciation percentage cannot be blank',
                        'depreciate-' . $i . '-duration' => 'Commission depreciation duration cannot be blank',
                        'depreciate-' . $i . '-start-date' => 'Commission depreciation start date cannot be blank']);
                }
            }
        }

        if ($req->input('useCustomField') == 'true') {
            $validationRules = array_merge($validationRules, ['custom-tracker' => 'required']);
            $validationMessages = array_merge($validationMessages, ['custom-tracker.required' => 'Tracking Field Name is required.']);
        }

        $this->validate($req, $validationRules, $validationMessages);

        $contactRepo = new Repos\ContactRepo();
        $addressRepo = new Repos\AddressRepo();
        $emailAddressRepo = new Repos\EmailAddressRepo();
        $accountRepo = new Repos\AccountRepo();
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

                $fName = $req->input('contact-' . $contactId . '-first-name');
                $lName = $req->input('contact-' . $contactId . '-last-name');
                $ppn = $req->input('contact-' . $contactId . '-phone1');
                $ppnExt = $req->input('contact-' . $contactId . '-phone1-ext');
                $spn = $req->input('contact-' . $contactId . '-phone2');
                $spnExt = $req->input('contact-' . $contactId . '-phone2-ext');
                $em = $req->input('contact-' . $contactId . '-email1');
                $em2 = $req->input('contact-' . $contactId . '-email2');

                $contact = [
                    'first_name'=>$fName,
                    'last_name'=>$lName
                ];

                $primary_id = null;

                if ($primaryAction == "new") {
                    $primary_id = $contactRepo->Insert($contact)->contact_id;
                    $contactId = $primary_id;
                }
                else if ($primaryAction == "update") {
                    $contact["contact_id"] = $contactId;
                    $contactRepo->Update($contact);
                }

                $phone1 = [
                    'phone_number'=>$ppn,
                    'extension_number'=>$ppnExt,
                    'is_primary'=>true,
                    'contact_id'=>$contactId
                ];

                $email1 = [
                    'email'=>$em,
                    'contact_id'=>$contactId,
                    'is_primary'=>true
                ];

                if ($primaryAction == "new") {
                    //New phone numbers on new account
                    $pnRepo->Insert($phone1);
                    $emailAddressRepo->Insert($email1);
                } else if ($primaryAction == "update") {
                    //New phone numbers on existing account
                    $phone1["phone_number_id"] = $req->input('contact-' . $contactId . '-phone1-id');
                    $pnRepo->Update($phone1);

                    $email1["email_address_id"] = $req->input('contact-' . $contactId . '-email1-id');
                    $emailAddressRepo->Update($email1);

                    if ($req->input('pn-action-add-' . $contactId) != null) {
                        $phone2 = [
                            'phone_number' => $spn,
                            'extension_number' => $spnExt,
                            'is_primary' => false,
                            'contact_id' => $contactId
                        ];
                        $pnRepo->Insert($phone2);
                    }
                    if ($req->input('em-action-add-' . $contactId) != null) {
                        if ($req->input('primary-email2') != null) {
                            $primary_email2 = [
                                'email' => $em2,
                                'contact_id' => $contactId
                            ];
                            $emailAddressRepo->Insert($primary_email2);
                        }
                    }

                    //Existing phone numbers on existing account
                    if ($req->input('contact-' . $contactId . '-phone2-id') != null) {
                        $phone2 = [
                            'phone_number_id' => $req->input('contact-' . $contactId . '-phone2-id'),
                            'phone_number' => $spn,
                            'extension_number' => $spnExt,
                            'is_primary' => false,
                            'contact_id' => $contactId
                        ];
                        $pnRepo->Update($phone2);
                    }
                    if ($req->input('contact-' . $contactId . '-email2-id') != null) {
                        if ($req->input('primary-email2') != null) {
                            $email2 = [
                                'email' => $em2,
                                'is_primary' => false,
                                'contact_id' => $contactId
                            ];
                            $emailAddressRepo->Update($email2);
                        }
                    }
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
        $delivery = [
            'street'=>$req->input('delivery-street'),
            'street2'=>$req->input('delivery-street2'),
            'city'=>$req->input('delivery-city'),
            'zip_postal'=>$req->input('delivery-zip-postal'),
            'state_province'=>$req->input('delivery-state-province'),
            'country'=>$req->input('delivery-country'),
            'is_primary'=>true
        ];

        $delivery_id = $req->input('delivery-id');

        if ($delivery_id == null)
            $delivery_id = $addressRepo->Insert($delivery)->address_id;
        else {
            $delivery["address_id"] = $delivery_id;
            $addressRepo->Update($delivery);
        }

        //END delivery address
        //BEGIN billing address
        $billing_id = $req->input('billing-id');
        if ($req->input('billing-address') == 'on') {
            $billing = [
                'street'=>$req->input('billing-street'),
                'street2'=>$req->input('billing-street2'),
                'city'=>$req->input('billing-city'),
                'zip_postal'=>$req->input('billing-zip-postal'),
                'state_province'=>$req->input('billing-state-province'),
                'country'=>$req->input('billing-country'),
                'is_primary'=>false
            ];

            if ($billing_id == null)
                $billing_id = $addressRepo->Insert($billing)->address_id;
            else {
                $billing["address_id"] = $billing_id;
                $addressRepo->Update($billing);
            }
        } else if ($billing_id != null)
                $addressRepo->Delete($billing_id);

        //END billing address
        //BEGIN account
        $old_acct = null;
        if ($req->input('account-number') != '') {
            $old_acct = $req->input('account-number');
        }

        $hasParent = $req->input('parent-account-id') != null && strlen($req->input('parent-account-id')) > 0;
        $getsDiscount = $req->input('discount') != null && $req->input('discount') > 0;

        $account = [
            'rate_type_id'=>1,
            'billing_address_id'=>$billing_id,
            'shipping_address_id'=>$delivery_id,
            'account_number'=>$old_acct,
            'invoice_interval'=>$req->input('invoice-interval'),
            'invoice_comment'=>$req->input('comment'),
            'stripe_id'=>40,
            'name'=>$req->input('name'),
            'start_date'=>strtotime($req->input('start-date')),
            'send_bills'=>$req->input('send-bills') == "true",
            'is_master'=>!$hasParent,
            'parent_account_id'=>!$hasParent ? null : $req->input('parent-account-id'),
            'gets_discount' => $getsDiscount,
            'discount'=> $getsDiscount ? $req->input('discount') / 100 : 0,
            'gst_exempt'=>$req->input('isGstExempt') == "true",
            'charge_interest'=>$req->input('shouldChargeInterest') == "true",
            'can_be_parent'=>$req->input('canBeParent') == "true",
            'active'=>true
        ];

        if ($req->input('useCustomField') == 'true')
            $customField = $req->input('custom-tracker');
        else
            $customField = null;
        $account["uses_custom_field"] = $req->input('useCustomField') == "true";
        $account['custom_field'] = $customField;

        $fuelsurcharge = null;
		if ($req->input('has-fuel-surcharge') == 'true') {
            $fuelsurcharge = $req->input('fuel-surcharge');
            $account['fuel_surcharge'] = $fuelsurcharge / 100;
        } else
            $account['fuel_surcharge'] = 0;

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
        $commission1 = $commission2 = null;

        if ($req->input('give-commission-1') == 'true') {
            $commission1 = [
                'commission_id' => $req->input('commission-1-id'),
                'account_id' => $accountId,
                'driver_id' => $req->input('commission-1-employee-id'),
                'commission' => $req->input('commission-1-percent'),
                'depreciation_amount' => $req->input('commission-1-depreciation-percent'),
                'years' => $req->input('commission-1-depreciation-duration'),
                'start_date' => $req->input('commission-1-depreciation-start-date')
            ];
        }

        if ($req->input('give-commission-2') == 'true') {
            $commission2 = [
                'commission_id' => $req->input('commission-2-id'),
                'account_id' => $accountId,
                'driver_id' => $req->input('commission-employee-2-id'),
                'commission' => $req->input('commission-2-percent'),
                'depreciation_amount' => $req->input('commission-2-depreciation-percent'),
                'years' => $req->input('commission-2-depreciation-duration'),
                'start_date' => $req->input('commission-2-depreciation-start-date')
            ];
        }

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
}
