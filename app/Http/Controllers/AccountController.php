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

    public function create() {
        //Check user settings and return popout or inline based on that
        //Check permissions
        $parents =  []; //Account::where('is_master', 'true')->pluck('name', 'account_id');

        return view('accounts.create_account', compact('parents'));
    }

    public function store(Request $req) {
        //Make sure the user has access to edit both: orig_bill and number (both are bill numbers, orig_bill will be the one to modify or -1 to create new)
        //return $req;

        $validationRules = [
            'name' => 'required',
            'primary-first-name' => 'required',
            'primary-last-name' => 'required',
            //Regex used found here: http://www.regexlib.com/REDetails.aspx?regexp_id=607
            'primary-phone1' => ['required', 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'primary-phone2' => ['regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'primary-email1' => 'required|email',
            'primary-email2' => 'email',
            'delivery-street' => 'required',
            'delivery-city' => 'required',
            //Regex used found here: http://regexlib.com/REDetails.aspx?regexp_id=417
            'delivery-zip-postal' => ['required', 'regex:/^((\d{5}-\d{4})|(\d{5})|([AaBbCcEeGgHhJjKkLlMmNnPpRrSsTtVvXxYy]\d[A-Za-z]\s?\d[A-Za-z]\d))$/'],
            'delivery-state-province' => 'required',
            'delivery-country' => 'required'
        ];

        $validationMessages = [
            'name.required' => 'Company Name is required.',
            'primary-first-name.required' => 'Primary Contact First Name is required.',
            'primary-last-name.required' => 'Primary Contact Last Name is required.',
            'primary-phone1.required' => 'Primary Contact Primary Phone Number is required.',
            'primary-phone1.regex' => 'Primary Contact Primary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'primary-phone2.regex' => 'Primary Contact Secondary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'primary-email1.required' => 'Primary Contact Primary Email is required.',
            'primary-email1.email' => 'Primary Contact Primary Email must be an email.',
            'primary-email2.email' => 'Primary Contact Secondary Email must be an email.',
            'delivery-street.required' => 'Delivery Address Street is required.',
            'delivery-city.required' => 'Delivery Address City is required.',
            'delivery-zip-postal.required' => 'Delivery Address Postal Code is required.',
            'delivery-zip-postal.regex' => 'Delivery Postal Code must be in the format "Q4B 5C5", "501-342", or "123324".',
            'delivery-state-province.required' => 'Delivery Province is required.',
            'delivery-country.required' => 'Delivery Country is required.'
        ];

        if ($req->input('secondary-contact') == 'on') {
            $validationRules = array_merge($validationRules, [
                'secondary-first-name' => 'required',
                'secondary-last-name' => 'required',
                'secondary-phone1' => ['required', 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
                'secondary-phone2' => ['regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
                'secondary-email1' => 'required|email',
                'secondary-email2' => 'email'
            ]);

            $validationMessages = array_merge($validationMessages, [
                'secondary-first-name.required' => 'Secondary Contact First Name is required.',
                'secondary-last-name.required' => 'Secondary Contact Last Name is required.',
                'secondary-phone1.required' => 'Secondary Contact Primary Phone Number is required.',
                'secondary-phone1.regex' => 'Secondary Contact Primary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
                'secondary-phone2.regex' => 'Secondary Contact Secondary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
                'secondary-email1.required' => 'Secondary Contact Primary Email is required.',
                'secondary-email1.email' => 'Secondary Contact Primary Email must be an email.',
                'secondary-email2.email' => 'Secondary Contact Secondary Email must be an email.',
            ]);
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

        $this->validate($req, $validationRules, $validationMessages);

        $contactRepo = new Repos\ContactRepo();
        $addressRepo = new Repos\AddressRepo();
        $emailAddressRepo = new Repos\EmailAddressRepo();
        $accountRepo = new Repos\AccountRepo();
        $pnRepo = new Repos\PhoneNumberRepo();

        //BEGIN primary Contact
        $primary_contact = [
            'first_name'=>$req->input('primary-first-name'),
            'last_name'=>$req->input('primary-last-name')
        ];
        $primary_id = $contactRepo->Insert($primary_contact)->contact_id;

        $primary_phone1 = [
            'phone_number'=>$req->input('primary-phone1'),
            'is_primary'=>true,
            'contact_id'=>$primary_id
        ];
        $pnRepo->Insert($primary_phone1);

        if ($req->input('primary-phone2') != null) {
            $primary_phone2 = [
                'phone_number'=>$req->input('primary-phone2'),
                'is_primary'=>false,
                'contact_id'=>$primary_id
            ];
            $pnRepo->Insert($primary_phone2);
        }

        if ($req->input('primary-email1') != null) {
            $primary_email1 = [
                'email'=>$req->input('primary-email1'),
                'contact_id'=>$primary_id
            ];
            $emailAddressRepo->Insert($primary_email1);
        }

        if ($req->input('primary-email2') != null) {
            $primary_email2 = [
                'email'=>$req->input('primary-email2'),
                'contact_id'=>$primary_id
            ];
            $emailAddressRepo->Insert($primary_email2);
        }
        //END primary contact
        $secondary_id = null;
        //BEGIN secondary contact
        if ($req->input('secondary-contact') == 'on') {
            $secondary_contact = [
                'first_name'=>$req->input('secondary-first-name'),
                'last_name'=>$req->input('secondary-last-name'),
            ];
            $secondary_id = $contactRepo->Insert($secondary_contact)->contact_id;

            $secondary_phone1 = [
                'phone_number'=>$req->input('secondary-phone1'),
                'is_primary'=>true,
                'contact_id'=>$secondary_id
            ];
            $pnRepo->Insert($secondary_phone1);

            if ($req->input('secondary-phone2') != null) {
                $secondary_phone2 = [
                    'phone_number'=>$req->input('secondary-phone2'),
                    'is_primary'=>false,
                    'contact_id'=>$secondary_id
                ];
                $pnRepo->Insert($secondary_phone2);
            }

            if ($req->input('secondary-email1') != null) {
                $secondary_email1 = [
                    'email'=>$req->input('secondary-email1'),
                    'is_primary'=>true,
                    'contact_id'=>$secondary_id
                ];
                $emailAddressRepo->Insert($secondary_email1);
            }

            if ($req->input('secondary-email2') != null) {
                $secondary_email2 = [
                    'email'=>$req->input('secondary-email2'),
                    'is_primary'=>false,
                    'contact_id'=>$secondary_id
                ];
                $emailAddressRepo->Insert($secondary_email2);
            }
        }
        //END secondary contact
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
        $delivery_id = $addressRepo->Insert($delivery)->address_id;
        //END delivery address
        //BEGIN billing address
        $billing_id = null;
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
            $billing_id = $addressRepo->Insert($billing)->address_id;
        }
        //END billing address
        //BEGIN account
        $old_acct = null;
        if ($req->input('account-number') != '') {
            $old_acct = $req->input('account-number');
        }

        $account = [
            'rate_type_id'=>1,
            'parent_account_id'=>$req->input('parent-account-id'),
            'billing_address_id'=>$billing_id,
            'shipping_address_id'=>$delivery_id,
            'account_number'=>$old_acct,
            'invoice_interval'=>$req->input('invoice-interval'),
            'stripe_id'=>40,
            'name'=>$req->input('name'),
            'start_date'=>time(),
            'send_bills'=>true,
            'is_master'=>true
        ];

        $accountRepo->Insert($account, $primary_id, $secondary_id)->account_id;

        //END account
        return redirect()->action('AccountController@create');
    }

    public function edit($id) {
        $factory = new Account\AccountModelFactory();
        $model = $factory->GetById($id);

        return view('account.edit_account', compact('model'));
    }

    public function submitEdit(Request $req) {
        $validationRules = [
            'account-id' => 'required',
            'name' => 'required',
            'primary-id' => 'required',
            'primary-first-name' => 'required',
            'primary-last-name' => 'required',
            //Regex used found here: http://www.regexlib.com/REDetails.aspx?regexp_id=607
            'primary-phone1' => ['required', 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'primary-phone2' => ['regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'primary-email1' => 'required|email',
            'primary-email2' => 'email',
            'delivery-street' => 'required',
            'delivery-city' => 'required',
            //Regex used found here: http://regexlib.com/REDetails.aspx?regexp_id=417
            'delivery-zip-postal' => ['required', 'regex:/^((\d{5}-\d{4})|(\d{5})|([AaBbCcEeGgHhJjKkLlMmNnPpRrSsTtVvXxYy]\d[A-Za-z]\s?\d[A-Za-z]\d))$/'],
            'delivery-state-province' => 'required',
            'delivery-country' => 'required'
        ];

        $validationMessages = [
            'account-id.required' => 'Account ID is missing. Please contact support.',
            'name.required' => 'Company Name is required.',
            'primary-id.required' => 'Primary Contact ID is missing. Please contact support.',
            'primary-first-name.required' => 'Primary Contact First Name is required.',
            'primary-last-name.required' => 'Primary Contact Last Name is required.',
            'primary-phone1.required' => 'Primary Contact Primary Phone Number is required.',
            'primary-phone1.regex' => 'Primary Contact Primary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'primary-phone2.regex' => 'Primary Contact Secondary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'primary-email1.required' => 'Primary Contact Primary Email is required.',
            'primary-email1.email' => 'Primary Contact Primary Email must be an email.',
            'primary-email2.email' => 'Primary Contact Secondary Email must be an email.',
            'delivery-street.required' => 'Delivery Address Street is required.',
            'delivery-city.required' => 'Delivery Address City is required.',
            'delivery-zip-postal.required' => 'Delivery Address Postal Code is required.',
            'delivery-zip-postal.regex' => 'Delivery Postal Code must be in the format "Q4B 5C5", "501-342", or "123324".',
            'delivery-state-province.required' => 'Delivery Province is required.',
            'delivery-country.required' => 'Delivery Country is required.'
        ];

        if ($req->input('secondary-contact') == 'on') {
            $validationRules = array_merge($validationRules, [
                'secondary-id' => 'required',
                'secondary-first-name' => 'required',
                'secondary-last-name' => 'required',
                'secondary-phone1' => ['required', 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
                'secondary-phone2' => ['regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
                'secondary-email1' => 'required|email',
                'secondary-email2' => 'email'
            ]);

            $validationMessages = array_merge($validationMessages, [
                'secondary-id.required' => 'Secondary Contact ID is missing. Please contact support.',
                'secondary-first-name.required' => 'Secondary Contact First Name is required.',
                'secondary-last-name.required' => 'Secondary Contact Last Name is required.',
                'secondary-phone1.required' => 'Secondary Contact Primary Phone Number is required.',
                'secondary-phone1.regex' => 'Secondary Contact Primary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
                'secondary-phone2.regex' => 'Secondary Contact Secondary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
                'secondary-email1.required' => 'Secondary Contact Primary Email is required.',
                'secondary-email1.email' => 'Secondary Contact Primary Email must be an email.',
                'secondary-email2.email' => 'Secondary Contact Secondary Email must be an email.',
            ]);
        }

        if ($req->input('billing-address') == 'on') {
            $validationRules = array_merge($validationRules, [
                'billing-id' => 'required',
                'billing-street' => 'required',
                'billing-city' => 'required',
                'billing-zip-postal' => ['required', 'regex:/^((\d{5}-\d{4})|(\d{5})|([AaBbCcEeGgHhJjKkLlMmNnPpRrSsTtVvXxYy]\d[A-Za-z]\s?\d[A-Za-z]\d))$/'],
                'billing-state-province' => 'required',
                'billing-country' => 'required'
            ]);

            $validationMessages = array_merge($validationMessages, [
                'billing-id.required' => 'Billing Address ID is missing. Please contact support.',
                'billing-street.required' => 'Billing Address Street is required.',
                'billing-city.required' => 'Billing Address City is required.',
                'billing-zip-postal.required' => 'Billing Address Postal Code is required.',
                'billing-zip-postal.regex' => 'Billing Postal Code must be in the format "Q4B 5C5", "501-342", or "123324".',
                'billing-state-province.required' => 'Billing Province is required.',
                'billing-country.required' => 'Billing Country is required.'
            ]);
        }

        $this->validate($req, $validationRules, $validationMessages);

        $contactRepo = new Repos\ContactRepo();
        $addressRepo = new Repos\AddressRepo();
        $acctRepo = new Repos\AccountRepo();
        $pnRepo = new Repos\PhoneNumberRepo();
        $emailAddressRepo = new Repos\EmailAddressRepo();

        //BEGIN primary Contact

        $primary_contact = [
            'contact_id'=>$req->input('primary-id'),
            'first_name'=>$req->input('primary-first-name'),
            'last_name'=>$req->input('primary-last-name')];
        $contactRepo->Edit($primary_contact);

        $primary_phone1 = [
            'phone_number_id'=>$req->input('primary-phone1-id'),
            'phone_number'=>$req->input('primary-phone1'),
            'is_primary'=>true];
        $pnRepo->Edit($primary_phone1);

        if ($req->input('primary-phone2') != null) {
            if ($req->input('primary-phone2-id') != null)
                $pnRepo->Delete($req->input('primary-phone2-id'));
        } else {
            $primary_phone2 = [
                'phone_number_id'=>$req->input('primary-phone2-id'),
                'phone_number'=>$req->input('primary-phone2'),
                'is_primary'=>false];
            $pnRepo->Edit($primary_phone2);
        }

        if ($req->input('primary-email1') == null) {
            if ($req->input('primary-email1-id') != null)
                $emailAddressRepo->Delete($req->input('primary-email1-id'));
        } else {
            $primary_email1 = [
                'email_address_id'=>$req->input('primary-email1-id'),
                'email'=>$req->input('primary-email1')];
            $emailAddressRepo->Edit($primary_email1);
        }

        if ($req->input('primary-email2') == null) {
            if ($req->input('primary-email2-id') != null)
                $emailAddressRepo->Delete($req->input('primary-email2-id'));
        } else {
            $primary_email2 = [
                'email_address_id'=>$req->input('primary-email2-id'),
                'email'=>$req->input('primary-email2')];
            $emailAddressRepo->Edit($primary_email2);
        }
        //END primary contact
        //BEGIN secondary contact
        if ($req->input('secondary-contact') == 'on') {
            $secondary_contact = [
                'contact_id'=>$req->input('secondary-id'),
                'first_name'=>$req->input('secondary-first-name'),
                'last_name'=>$req->input('secondary-last-name')];
            $contactRepo->Edit($secondary_contact);

            $secondary_phone1 = [
                'phone_number_id'=>$req->input('secondary-phone1-id'),
                'phone_number'=>$req->input('secondary-phone1'),
                'is_primary'=>true];
            $pnRepo->Edit($secondary_phone1);

            if ($req->input('secondary-phone2') == null) {
                if ($req->input('secondary-phone1-id') != null)
                    $pnRepo->Delete($req->input('secondary-phone1-id'));
            } else {
                $secondary_phone2 = [
                    'phone_number_id'=>$req->input('secondary-phone2-id'),
                    'phone_number'=>$req->input('secondary-phone2'),
                    'is_primary'=>false];
                $pnRepo->Edit($secondary_phone2);
            }

            if ($req->input('secondary-email1') == null) {
                if ($req->input('secondary-email1-id') != null)
                    $emailAddressRepo->Delete($req->input('secondary-email1-id'));
            } else {
                $secondary_email1 = [
                    'email_address_id'=>$req->input('secondary-email1-id'),
                    'email'=>$req->input('secondary-email1')];
                $emailAddressRepo->Edit($secondary_email1);
            }

            if ($req->input('secondary-email2') == null) {
                if ($req->input('secondary-email2-id') != null)
                    $emailAddressRepo->Delete($req->input('secondary-email2-id'));
            } else {
                $secondary_email2 = [
                    'email_address_id'=>$req->input('secondary-email2-id'),
                    'email'=>$req->input('secondary-email2')];
                $emailAddressRepo->Edit($secondary_email2);
            }
        } else {
            if ($req->input('secondary-id') != null)
                $contactRepo->Delete($req->input('secondary-id'));
        }
        //END secondary contact
        //BEGIN delivery address
        $delivery = [
            'address_id'=>$req->input('delivery-id'),
            'street'=>$req->input('delivery-street'),
            'street2'=>$req->input('delivery-street2'),
            'city'=>$req->input('delivery-city'),
            'zip_postal'=>$req->input('delivery-zip-postal'),
            'state_province'=>$req->input('delivery-state-province'),
            'country'=>$req->input('delivery-country')];
        $addressRepo->Edit($delivery);
        //END delivery address
        //BEGIN billing address
        if ($req->input('billing-address') == 'on') {
            $billing = [
                'address_id'=>$req->input('billing-id'),
                'street'=>$req->input('billing-street'),
                'street2'=>$req->input('billing-street2'),
                'city'=>$req->input('billing-city'),
                'zip_postal'=>$req->input('billing-zip-postal'),
                'state_province'=>$req->input('billing-state-province'),
                'country'=>$req->input('billing-country')];
            $addressRepo->Edit($billing);
        } else {
            if ($req->input('billing-id') != null)
                $addressRepo->Delete($req->input('billing-id'));
        }
        //END billing address
        //BEGIN account
        $old_acct = null;
        if ($req->input('account-number') != '') {
            $old_acct = $req->input('account-number');
        }

        $account = [
            'account_id'=>$req->input('account-id'),
            'rate_type_id'=>1,
            'parent_account_id'=>$req->input('parent-account-id'),
            'account_number'=>$old_acct,
            'invoice_interval'=>$req->input('invoice-interval'),
            //TODO: Handle stripe id
            'stripe_id'=>40,
            'name'=>$req->input('name'),
            'start_date'=>time(),
            'send_bills'=>true,
            'is_master'=>false,];
        $acctRepo->Edit($account);

        //END account
        return redirect()->action('AccountController@edit');
    }
}
