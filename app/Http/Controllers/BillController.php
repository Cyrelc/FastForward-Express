<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
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
        $billModelFactory = new Bill\BillModelFactory();
        $driverRepo = new Repos\DriverRepo();
        $model = $billModelFactory->GetBillAdvancedFiltersModel();
        $drivers = $driverRepo->ListAllWithEmployeeAndContact();

        return view('bills.bills', compact('model', 'drivers'));
    }

    public function buildTable(Request $req) {
        $billRepo = new Repos\BillRepo();
        return $billRepo->ListAll($req->filter);
    }

    public function create(Request $req) {
        // Check permissions
        $bill_model_factory = new Bill\BillModelFactory();
        $model = $bill_model_factory->GetCreateModel($req);
        return view('bills.bill', compact('model'));
    }

    public function edit(Request $req, $id) {
        $billRepo = new Repos\BillRepo();
        $factory = new Bill\BillModelFactory();
        $model = $factory->GetEditModel($id, $req);
        return view('bills.bill', compact('model'));
    }

    public function delete(Request $req, $id) {
        $billRepo = new Repos\BillRepo();

        if ($billRepo->IsReadOnly($id)) {
            return ('Unable to delete. Bill is locked');
        } else {
            $billRepo->Delete($id);
            return redirect()->action('BillController@index');
        }
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try {
            $billValidation = new \App\Http\Validation\BillValidationRules();
            $temp = $billValidation->GetValidationRules($req);

            $validationRules = $temp['rules'];
            $validationMessages = $temp['messages'];

            $this->validate($req, $validationRules, $validationMessages);

            $acctRepo = new Repos\AccountRepo();
            $billRepo = new Repos\BillRepo();
            $addrRepo = new Repos\AddressRepo();
            $packageRepo = new Repos\PackageRepo();
            $chargebackRepo = new Repos\ChargebackRepo();
            $paymentRepo = new Repos\PaymentRepo();

            $addrCollector = new \App\Http\Collectors\AddressCollector();
            $billCollector = new \App\Http\Collectors\BillCollector();
            $packageCollector = new \App\Http\Collectors\PackageCollector();

            $pickupAddress = $addrCollector->CollectForAccount($req, 'pickup', false);
            $deliveryAddress = $addrCollector->CollectForAccount($req, 'delivery', false);

            if ($req->bill_id) {
                $pickupAddressId = $addrRepo->Update($pickupAddress)->address_id;
                $deliveryAddressId = $addrRepo->Update($deliveryAddress)->address_id;
            }
            else {
                $pickupAddressId = $addrRepo->Insert($pickupAddress)->address_id;
                $deliveryAddressId = $addrRepo->Insert($deliveryAddress)->address_id;
            }

            $old_bill = isset($req->bill_id) ? $billRepo->getById($req->bill_id) : null;
            $payment_id = $req->charge_type == '' ? null : $this->getPaymentId($old_bill, $req);

            $bill = $billCollector->Collect($req, $pickupAddressId, $deliveryAddressId, $payment_id);

            if($req->bill_id)
                $bill = $billRepo->Update($bill);
            else
                $bill = $billRepo->Insert($bill);

            $packages = $packageCollector->Collect($req, $bill->bill_id);

            if($bill->bill_id) {
                $old_packages = $packageRepo->GetByBillId($bill->bill_id);
                $old_package_ids = [];
                $new_package_ids = [];
                foreach($old_packages as $old_package)
                    array_push($old_package_ids, $old_package->package_id);
                foreach($packages as $package)
                    array_push($new_package_ids, $package['package_id']);
                $delete_package_ids = array_diff($old_package_ids, $new_package_ids);
                foreach($delete_package_ids as $delete_id)
                    $packageRepo->Delete($delete_id);
            }

            foreach($packages as $package) {
                if ($package['package_id'] == 'null')
                    $packageRepo->Insert($package);
                else
                    $packageRepo->Update($package);
            }

            //if a previous payment method exists, that does not match the currently submitted payment method
            //then delete the old payment record if necessary
            if($old_bill != null && $req->charge_type != 'prepaid' && $old_bill->payment_id != null)
                $paymentRepo->Delete($old_bill->payment_id);
            elseif($old_bill != null && $req->charge_type != 'driver' && $old_bill->chargeback_id != null)
                $chargebackRepo->Delete($old_bill->chargeback_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'id' => $bill->bill_id
            ]);
            
        } catch(Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getPaymentId($old_bill, $req) {
        $chargebackRepo = new Repos\ChargebackRepo();
        $paymentRepo = new Repos\PaymentRepo();

        switch ($req->charge_type) {
            case 'account':
                return $req->charge_account_id;
            case 'driver':
                $amount = ($req->amount == '' ? 0 : $req->amount) + ($req->interliner_cost_to_customer == '' ? 0 : $req->interliner_cost_to_customer);
                if($old_bill != null && $old_bill->chargeback_id != null) {
                    $data = new \Illuminate\Http\Request();
                    $data->replace(['amount' => $amount]);
                    $chargebackRepo->Update($old_bill->chargeback_id, $data);
                    return $old_bill->chargeback_id;
                } else {
                    $chargeback = [
                        'employee_id' => $req->charge_driver_id,
                        'amount' => $amount,
                        'gl_code' => null,
                        'name' => 'Bill Chargeback',
                        'description' => $req->description,
                        'continuous' => false,
                        'count_remaining' => 0,
                        'start_date' => (new \DateTime($req->input('start_date')))->format('Y-m-d')
                    ];
                    return ($chargebackRepo->CreateBillChargeback($chargeback))->chargeback_id;
                }
            case 'prepaid':
                if($old_bill != null && $old_bill->payment_id != null) {
                    $payment = (new \App\Http\Collectors\PaymentCollector())->CollectBillPayment($req);
                    return ($paymentRepo->Update($old_bill->payment_id, $payment))->payment_id;
                } else {
                    $payment = (new \App\Http\Collectors\PaymentCollector())->CollectBillPayment($req);
                    return ($paymentRepo->Insert($payment))->payment_id;
                }
        }
    }
}
