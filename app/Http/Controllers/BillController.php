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

        $acctRepo = new Repos\AccountRepo();
        $billRepo = new Repos\BillRepo();
        $addrRepo = new Repos\AddressRepo();
        $addrCollector = new \App\Http\Collectors\AddressCollector();
        $billCollector = new \App\Http\Collectors\BillCollector();

        switch ($req->charge_selection_submission) {
            case "pickup_account":
                $chargeAccountId = $req->pickup_account_id;
                break;
            case "delivery_account":
                $chargeAccountId = $req->delivery_account_id;
                break;
            case "other_account" :
                $chargeAccountId = $req->charge_account_id;
                break;
            case "pre-paid":
                $chargeAccountId = null;
                break;
        }

        switch ($req->pickup_use_submission) {
            case "account":
                $pickupAccount = $acctRepo->GetById($req->pickup_account_id);
                $pickupAddress = $addrRepo->GetById($pickupAccount->shipping_address_id);
                $pickupAddress = $addrCollector->ToArray($pickupAddress, 'false');
                break;
            case "address":
                $pickupAddress = $addrCollector->CollectForAccount($req, 'pickup', false);
                break;
        }
        if ($req->bill_id)
            $pickupAddressId = $addrRepo->Update($pickupAddress)->address_id;
        else
            $pickupAddressId = $addrRepo->Insert($pickupAddress)->address_id;

        switch ($req->delivery_use_submission) {
            case "account":
                $deliveryAccount = $acctRepo->GetById($req->delivery_account_id);
                $deliveryAddress = $addrRepo->GetById($deliveryAccount->shipping_address_id);
                $deliveryAddress = $addrCollector->ToArray($deliveryAddress, 'false');
                break;
            case "address":
                $deliveryAddress = $addrCollector->CollectForAccount($req, 'delivery', false);
                break;
        }
        if ($req->bill_id)
            $deliveryAddressId = $addrRepo->Update($deliveryAddress)->address_id;
        else
            $deliveryAddressId = $addrRepo->Insert($deliveryAddress)->address_id;

        $bill = $billCollector->Collect($req, $chargeAccountId, $pickupAddressId, $deliveryAddressId);
        if ($req->bill_id) {
            $billRepo->Update($bill);
            return redirect()->action('BillController@index');
        } else {
            $billRepo->Insert($bill);
            return redirect()->action('BillController@create');
        }
    }

}
