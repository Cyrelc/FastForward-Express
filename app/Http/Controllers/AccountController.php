<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Requests;
use App\Account;
use App\Contact;
use App\PhoneNumber;
use App\EmailAddress;
use App\Address;
use App\AccountContact;

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
        $contents = Account::all();

        return view('customers.customers', compact('contents'));
    }

    public function create() {
        //Check user settings and return popout or inline based on that
        //Check permissions
        $parents =  []; //Account::where('is_master', 'true')->pluck('name', 'account_id');

        return view('customers.create_customer', compact('parents'));
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
        $account = new Account();

        $delivery = ['street'=>$req->input('delivery-street'),
                     'street2'=>$req->input('delivery-street2'),
                     'city'=>$req->input('delivery-city'),
                     'zip_postal'=>$req->input('delivery-zip-postal'),
                     'state_province'=>$req->input('delivery-state-province'),
                     'country'=>$req->input('delivery-country')];
        $delivery_id = DB::table('addresses')->insertGetId($delivery,'address_id');

        $primary_contact = ['first_name'=>$req->input(''),
                           'last_name'=>$req->input('')];
        $primary_id = DB::table('contacts')->insertGetId($primary_contact. 'contact_id');

        $primary_phone_1 = [//'type'=>$req->input('primary-phone1-type'),
                            'phone_number'=>$req->input('primary-phone1'),
                            'is_primary'=>false,
                            'contact_id'=>$primary_id];

        if ($req->input('billing-street') != null) {
            $billing = ['street'=>$req->input('billing-street'),
                        'street2'=>$req->input('billing-street2'),
                        'city'=>$req->input('billing-city'),
                        'zip_postal'=>$req->input('billing-zip-postal'),
                        'state_province'=>$req->input('billing-state-province'),
                        'country'=>$req->input('billing-country')];
            $billing_id = DB::table('addresses')->insertGetId($billing, 'address_id');
        }


    }
}
