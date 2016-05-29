<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class CustomerController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        return view('customers.customers');
    }

    public function getCustomersInt($input) {
        $maxcount = isset($input['max_count']) ?
                $input['max_count'] :
                env('DEFAULT_CUSTOMER_COUNT', 10000);

        $customers = Customer::all()->sortBy('company_name')
                ->slice(0, $maxcount)->values()->all();

        return [
            'success' => true,
            'customers' => $customers
        ];
    }

    public function getCustomers(Request $req) {
        return getCustomersInt($req->all());
    }
}
