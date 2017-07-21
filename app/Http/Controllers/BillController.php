<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Bill;

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
        // Check permissions
        $bill_model_factory = new Bill\BillModelFactory();
        $model = $bill_model_factory->GetCreateModel($req);
        return view('bills.bill', compact('model'));
    }

    public function edit(Request $req, $id) {
        $factory = new Bill\BillModelFactory();
        $model = $factory->GetEditModel($id, $req);
        return view('bills.bill', compact('model'));
    }

    public function store(Request $req) {
        $billValidation = new \App\Http\Validation\BillValidationRules();
        $temp = $billValidation->GetValidationRules($req);

        $validationRules = $temp['rules'];
        $validationMessages = $temp['messages'];

        $this->validate($req, $validationRules, $validationMessages);

        

        return;
    }
}
