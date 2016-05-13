<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Bill;

class BillController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        return view('bills.bills');
    }

    public function create() {
        //Check user settings and return popout or inline based on that
        //Check permissions
        return view('bills.bill_popout', array(
                'source' => 'Create',
                'action' => '/bill'
        ));
    }

    public function edit($id) {
        //Check user settings and return popout or inline based on that
        //Check permissions
        $bill = Bill::find($id);
        //Check for failure
        return view('bills.bill_popout', array(
                'source' => 'Edit',
                'action' => '/bill/' . $id . '/edit',
                'bill' => $bill
        ));
    }
}
