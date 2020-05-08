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
        return $billRepo->ListAll($req);
    }

    public function view(Request $req) {
        // Check permissions
        return view('bills.bill');
    }

    public function delete(Request $req, $id) {
        $billRepo = new Repos\BillRepo();

        if ($billRepo->IsReadOnly($id)) {
            return ('Unable to delete. Bill is locked');
        } else {
            DB::beginTransaction();
            try {
                $billRepo->Delete($id);

                DB::commit();
                return redirect()->action('BillController@index');
            } catch(Exception $e) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function getModel(Request $req, $id = null) {
        $billModelFactory = new Bill\BillModelFactory();
        if($id)
            $model = $billModelFactory->GetEditModel($req, $id);
        else
            $model = $billModelFactory->GetCreateModel($req);
        return json_encode($model);
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

            $oldBill = $billRepo->getById($req->bill_id);

            $pickupAddress = $addrCollector->CollectMinimal($req, 'pickup_address', $oldBill ? $oldBill->pickup_address_id : null);
            $deliveryAddress = $addrCollector->CollectMinimal($req, 'delivery_address', $oldBill ? $oldBill->delivery_address_id : null);

            if ($oldBill) {
                $pickupAddressId = $addrRepo->UpdateMinimal($pickupAddress)->address_id;
                $deliveryAddressId = $addrRepo->UpdateMinimal($deliveryAddress)->address_id;
            }
            else {
                $pickupAddressId = $addrRepo->InsertMinimal($pickupAddress)->address_id;
                $deliveryAddressId = $addrRepo->InsertMinimal($deliveryAddress)->address_id;
            }

            $payment_id = $req->payment_type ? $this->getPaymentId($oldBill, $req) : null;

            $bill = $billCollector->Collect($req, $pickupAddressId, $deliveryAddressId, $payment_id);

            if($oldBill)
                $bill = $billRepo->Update($bill);
            else
                $bill = $billRepo->Insert($bill);
            //if a previous payment method exists, that does not match the currently submitted payment method
            //then delete the old payment record if necessary
            if($oldBill != null && ($req->payment_type['name'] === 'Account' || $req->payment_type['name'] === 'Driver') && $oldBill->payment_id != null)
                $paymentRepo->Delete($oldBill->payment_id);
            elseif($oldBill != null && $req->payment_type['name'] != 'Driver' && $oldBill->chargeback_id != null)
                $chargebackRepo->Delete($oldBill->chargeback_id);

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

        switch ($req->payment_type['name']) {
            case 'Account':
                return $req->charge_account_id;
            case 'Driver':
                $amount = ($req->amount == '' ? 0 : $req->amount) + ($req->interliner_cost_to_customer == '' ? 0 : $req->interliner_cost_to_customer);
                if($old_bill != null && $old_bill->chargeback_id != null) {
                    $data = new \Illuminate\Http\Request();
                    $data->replace(['amount' => $amount]);
                    $chargebackRepo->Update($old_bill->chargeback_id, $data);
                    return $old_bill->chargeback_id;
                } else {
                    $chargeback = [
                        'employee_id' => $req->charge_employee_id,
                        'amount' => $amount,
                        'gl_code' => null,
                        'name' => 'Bill Chargeback',
                        'description' => $req->description,
                        'continuous' => false,
                        'count_remaining' => 1,
                        'start_date' => (new \DateTime($req->input('start_date')))->format('Y-m-d')
                    ];
                    return ($chargebackRepo->CreateBillChargeback($chargeback))->chargeback_id;
                }
            default:
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
