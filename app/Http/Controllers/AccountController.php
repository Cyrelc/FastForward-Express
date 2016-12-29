<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Requests;
use App\Contact;
use App\PhoneNumber;
use App\EmailAddress;
use App\Address;
use App\AccountContact;

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
        $contents = $factory->List();

        return view('accounts.accounts', compact('contents'));
    }

    public function create() {
        //Check user settings and return popout or inline based on that
        //Check permissions
        $parents =  []; //Account::where('is_master', 'true')->pluck('name', 'account_id');

        return view('accounts.create_account', compact('parents'));
    }

    public function edit($id) {
        //Check user settings and return popout or inline based on that
        //Check permissions
        $bill = Bill::where('number', '=', $id)->firstOrFail();
        //Fail a little nicer...
        return view('bills.bill_popout', array(
                'source' => 'Edit',
                'action' => '/bills',
                'bill' => $bill
        ));
    }

    public function store(Request $req, Account $acct) {
        //Make sure the user has access to edit both: orig_bill and number (both are bill numbers, orig_bill will be the one to modify or -1 to create new)

        //BEGIN primary Contact
        $rules = array();
        $val = new Validator();

        $primary_contact = ['first_name'=>$req->input('primary-first-name'),
                           'last_name'=>$req->input('primary-last-name'),
                           'address_id'=>null];
        $primary_id = DB::table('contacts')->insertGetId($primary_contact, 'contact_id');

        $primary_phone1 = [//'type'=>$req->input('primary-phone1-type'),
                            'phone_number'=>$req->input('primary-phone1'),
                            'is_primary'=>false,
                            'contact_id'=>$primary_id];
        DB::table('phone_numbers')->insert($primary_phone1);

        if ($req->input('primary-phone2') != null) {
            $primary_phone2 = [//'type'=>$req->input(''),
                               'phone_number'=>$req->input('primary-phone2'),
                               'is_primary'=>false,
                               'contact_id'=>$primary_id];
            DB::table('phone_numbers')->insert($primary_phone2);
        }

        if ($req->input('primary-email1') != null) {
            $primary_email1 = ['email'=>$req->input('primary-email1'),
                               'contact_id'=>$primary_id];
            DB::table('email_addresses')->insert($primary_email1);
        }

        if ($req->input('primary-email2') != null) {
            $primary_email2 = ['email'=>$req->input('primary-email2'),
                               'contact_id'=>$primary_id];
            DB::table('email_addresses')->insert($primary_email2);
        }
        //END primary contact
        $secondary_id = null;
        //BEGIN secondary contact
        if ($req->input('secondary-contact') == 'on') {
            $secondary_contact = ['first_name'=>$req->input('secondary-first-name'),
                                  'last_name'=>$req->input('secondary-last-name'),
                                  'address_id'=>null];
            $secondary_id = DB::table('contacts')->insertGetId($secondary_contact, 'contact_id');

            $secondary_phone1 = [//'type'=>$req->input('secondary-phone1-type'),
                                'phone_number'=>$req->input('secondary-phone1'),
                                'is_primary'=>false,
                                'contact_id'=>$secondary_id];
            DB::table('phone_numbers')->insert($secondary_phone1);

            if ($req->input('secondary-phone2') != null) {
                $secondary_phone2 = [//'type'=>$req->input(''),
                                     'phone_number'=>$req->input('secondary-phone2'),
                                     'is_primary'=>false,
                                     'contact_id'=>$secondary_id];
                DB::table('phone_numbers')->insert($secondary_phone2);
            }

            if ($req->input('secondary-email1') != null) {
                $secondary_email1 = ['email'=>$req->input('secondary-email1'),
                                   'contact_id'=>$secondary_id];
                DB::table('email_addresses')->insert($secondary_email1);
            }

            if ($req->input('secondary-email2') != null) {
                $secondary_email2 = ['email'=>$req->input('secondary-email2'),
                                   'contact_id'=>$secondary_id];
                DB::table('email_addresses')->insert($secondary_email2);
            }
        }
        //END secondary contact
        //BEGIN delivery address
        $delivery = ['street'=>$req->input('delivery-street'),
                     'street2'=>$req->input('delivery-street2'),
                     'city'=>$req->input('delivery-city'),
                     'zip_postal'=>$req->input('delivery-zip-postal'),
                     'state_province'=>$req->input('delivery-state-province'),
                     'country'=>$req->input('delivery-country')];
        $delivery_id = DB::table('addresses')->insertGetId($delivery,'address_id');
        //END delivery address
        //BEGIN billing address
        $billing_id = null;
        if ($req->input('billing-street') != null) {
            $billing = ['street'=>$req->input('billing-street'),
                        'street2'=>$req->input('billing-street2'),
                        'city'=>$req->input('billing-city'),
                        'zip_postal'=>$req->input('billing-zip-postal'),
                        'state_province'=>$req->input('billing-state-province'),
                        'country'=>$req->input('billing-country')];
            $billing_id = DB::table('addresses')->insertGetId($billing, 'address_id');
        }
        //END billing address
        //BEGIN account
        $old_acct = null;
        if ($req->input('account-number') != '') {
            $old_acct = $req->input('account-number');
        }

        $account = [//'rate_type_id'=>$req->input('rate-id'),
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
                    'is_master'=>false,];
        $account_id = DB::table('accounts')->insertGetId($account, 'account_id');

        $primary_account_link = ['account_id'=>$account_id,
                                 'contact_id'=>$primary_id,
                                 'is_primary'=>true];
        DB::table('account_contacts')->insert($primary_account_link);

        if ($secondary_id != null) {
            $secondary_account_link = ['account_id'=>$account_id,
                                       'contact_id'=>$secondary_id,
                                       'is_primary'=>false];
            DB::table('account_contacts')->insert($secondary_account_link);
        }
        //END account
        return redirect()->action('AccountController@create');
    }
}
