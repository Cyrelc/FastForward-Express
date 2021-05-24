<?php

namespace App\Http\Controllers;

use App\Events\BillCreated;
use App\Events\BillUpdated;
use App\Http\Repos;
use App\Http\Models\Bill;
use App\Http\Models\Permission;
use DB;
use Illuminate\Http\Request;
use Validator;

class BillController extends Controller {
    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'number';
        $this->maxCount = env('DEFAULT_BILL_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_BILL_AGE', '6 month');
        $this->class = new \App\Bill;
    }

    public function assignToInvoice($req, $billId, $invoiceId) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($invoiceId);
        if($req->user()->cannot('update', $invoice))
            abort(403);

        DB::beginTransaction();
        $invoiceId = $invoiceRepo->AssignBillToInvoice($invoiceId, $billId)->invoice_id;

        DB::commit();
        return $invoiceId;
    }

    public function buildTable(Request $req) {
        $user = $req->user();
        if($user->cannot('viewAny', Bill::class))
            abort(403);

        $accountRepo = new Repos\AccountRepo();
        $billRepo = new Repos\BillRepo();
        if(count($user->accountUsers) > 0)
            $bills = $billRepo->ListAll($req, $accountRepo->GetMyAccountIds($user, $user->can('bills.view.basic.children')));
        else if($user->can('viewAll', Bill::class))
            $bills = $billRepo->ListAll($req);
        else if($user->employee)
            $bills = $billRepo->ListAll($req, null, $req->user()->employee->employee_id);

        return json_encode($bills);
    }

    public function delete(Request $req, $billId) {
        $billRepo = new Repos\BillRepo();
        $bill = $billRepo->GetById($billId);
        if($req->user()->cannot('delete', $bill))
            abort(403);

        if ($billRepo->IsReadOnly($billId)) {
            throw new Exception('Unable to delete. Bill is locked');
        } else {
            DB::beginTransaction();

            $billRepo->Delete($billId);

            DB::commit();
            return response()->json([
                'success' => true
            ]);
        }
    }

    public function getModel(Request $req, $billId = null) {
        $billModelFactory = new Bill\BillModelFactory();
        $permissionModelFactory = new Permission\PermissionModelFactory();

        if($billId) {
            $billRepo = new Repos\BillRepo();
            $bill = $billRepo->GetById($billId);
            if($req->user()->cannot('viewBasic', $bill))
                abort(403);

            $permissions = $permissionModelFactory->GetBillPermissions($req->user(), $bill);
            $model = $billModelFactory->GetEditModel($billId, $permissions);
        } else {
            if($req->user()->cannot('createBasic', Bill::class))
                abort(403);
            $permissions = $permissionModelFactory->GetBillPermissions($req->user());
            $model = $billModelFactory->GetCreateModel($req, $permissions);
        }

        return json_encode($model);
    }

    public function removeFromInvoice($billId) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($invoiceId);
        if($req->user()->cannot('update', $invoice))
            abort(403);

        DB::beginTransaction();
        $invoiceRepo->RemoveBillFromInvoice($billId);

        DB::commit();
        return response()->json([
            'success' => true
        ]);
    }

    public function store(Request $req) {
        $billRepo = new Repos\BillRepo();
        $oldBill = $billRepo->getById($req->bill_id);

        if($oldBill) {
            if($req->user()->cannot('updateBasic', $oldBill) && $req->user()->cannot('updateDispatch', $oldBill) && $req->user()->cannot('updateBilling', $oldBill))
                abort(403);
        } else if ($req->user()->cannot('createBasic', Bill::class) && $req->user()->cannot('createFull', Bill::class))
            abort(403);

        $permissionModelFactory = new Permission\PermissionModelFactory();
        $permissions = $permissionModelFactory->GetBillPermissions($req->user(), $oldBill);

        $billValidation = new \App\Http\Validation\BillValidationRules();
        $temp = $billValidation->GetValidationRules($req, $oldBill, $permissions);

        $validationRules = $temp['rules'];
        $validationMessages = $temp['messages'];

        $this->validate($req, $validationRules, $validationMessages);

        $acctRepo = new Repos\AccountRepo();
        $addrRepo = new Repos\AddressRepo();
        $packageRepo = new Repos\PackageRepo();
        $chargebackRepo = new Repos\ChargebackRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $addrCollector = new \App\Http\Collectors\AddressCollector();
        $billCollector = new \App\Http\Collectors\BillCollector();
        $packageCollector = new \App\Http\Collectors\PackageCollector();

        $pickupAddress = $addrCollector->CollectMinimal($req, 'pickup_address', $oldBill ? $oldBill->pickup_address_id : null);
        $deliveryAddress = $addrCollector->CollectMinimal($req, 'delivery_address', $oldBill ? $oldBill->delivery_address_id : null);

        DB::beginTransaction();

        if ($oldBill) {
            $pickupAddressId = $addrRepo->UpdateMinimal($pickupAddress)->address_id;
            $deliveryAddressId = $addrRepo->UpdateMinimal($deliveryAddress)->address_id;
        } else {
            $pickupAddressId = $addrRepo->InsertMinimal($pickupAddress)->address_id;
            $deliveryAddressId = $addrRepo->InsertMinimal($deliveryAddress)->address_id;
        }

        $payment_id = $req->payment_type ? $this->getPaymentId($oldBill, $req) : null;

        $bill = $billCollector->Collect($req, $permissions, $pickupAddressId, $deliveryAddressId, $payment_id);

        if($oldBill)
            $bill = $billRepo->Update($bill, $permissions);
        else
            $bill = $billRepo->Insert($bill);
        //if a previous payment method exists, that does not match the currently submitted payment method
        //then delete the old payment record if necessary
        if($oldBill != null && ($req->payment_type['name'] === 'Account' || $req->payment_type['name'] === 'Driver') && $oldBill->payment_id != null)
            $paymentRepo->Delete($oldBill->payment_id);
        elseif($oldBill != null && $req->payment_type['name'] != 'Driver' && $oldBill->chargeback_id != null)
            $chargebackRepo->Delete($oldBill->chargeback_id);

        DB::commit();

        if($oldBill)
            event(new BillUpdated($bill));
        else
            event(new BillCreated($bill));

        return response()->json([
            'success' => true,
            'id' => $bill->bill_id,
            'updated_at' => $bill->updated_at
        ]);
    }

    /**
     * Private functions
     */
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
