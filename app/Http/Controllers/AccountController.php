<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $shipping = new Address();
        $contact = new Contact();

        $account->rate_type_id = 0;
        $account->parent_account_id = $req->parent_account_id;
        //address stuff
        $account->account_number = $req->account_number;
        $account->invoice_interval = $req->invoice_interval;
        //$account->stripe_id = new stripeID;
        $account->name = $req->name;
        // $account->start_date = date_timestamp_get();
        $account->send_bills = true;
        $account->is_master = false;
        

        /*
        //foreach contact
        $contact = new Contact();
        $phone1 = new PhoneNumber();
        //if phone2 is valid
        $email1 = new EmailAdress();
        //if email 2 is valid

        //if Contact does not already exist
        $contact->first_name = $req->first_name1;
        $contact->last_name = $req->last_name1;
        $phone1->phone_number = $req->primary_phone1;

        //submit contact, emails, and phone #s
        $relation = new ContactEmailAddress();
        //set relation(s)
        //submit
        $relation = new ContactPhoneNumber();
        //set relation(s)
        //submit
        */

        $account->name = $req->name;
        $account->rate_type_id = $req->rate_type_id;
        return $req;
    }
}
