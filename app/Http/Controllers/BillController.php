<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Validator;
use Redirect;

use App\Http\Requests;
use App\Bill;
use App\ReferenceType;

class BillController extends Controller {
    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'number';
        $this->maxCount = env('DEFAULT_BILL_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_BILL_AGE', '6 month');
        $this->class = new \App\Bill;
    }

    public function index() {
        $factory = new Bill\BillModelFactory();
        $contents = $factory->ListAll();

        return view('bills.bills', compact('contents'));
    }

    public function create(Request $req) {
        //Check user settings and return popout or inline based on that
        //Check permissions
        $bmf = new Bill\BillModelFactory();
        $model = $amf->GetCreateModel($req);
        return view('bills.bill', compact('model'));
    }

    public function edit(Request $req, $id) {
        $factory = new Bill\BillModelFactory();
        $model = $factory->GetEditModel($id, $req);
        return view('bills.bill', compact('model'));
    }

    public function store(Request $req) {
        return;
    }
}
