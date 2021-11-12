<?php

namespace App\Http\Controllers;

// Events
use App\Events\BillCreated;
use App\Events\BillUpdated;
// Classes
use App\Http\Collectors;
use App\Http\Repos;
use App\Http\Models\Bill;
use App\Http\Models\Permission;

use DB;
use Illuminate\Http\Request;
use Validator;

class BillController extends Controller {
    public function buildTable(Request $req) {
        $user = $req->user();
        if($user->cannot('viewAny', Bill::class))
            abort(403);

        $billModelFactory = new Bill\BillModelFactory();
        $bills = $billModelFactory->BuildTable($req);

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

            $accountRepo = new Repos\AccountRepo();

            $myAccounts = $accountRepo->GetMyAccountIds($req->user(), $req->user()->can('bills.view.basic.children'));
            $permissions = $permissionModelFactory->GetBillPermissions($req->user(), $bill);

            $model = $billModelFactory->GetEditModel($billId, $permissions, $myAccounts);
        } else {
            if($req->user()->cannot('createBasic', Bill::class))
                abort(403);
            $permissions = $permissionModelFactory->GetBillPermissions($req->user());
            $model = $billModelFactory->GetCreateModel($req, $permissions);
        }

        return json_encode($model);
    }

    public function manageLineItemLinks(Request $req) {
        $lineItemRepo = new Repos\LineItemRepo();

        $lineItem = $lineItemRepo->GetById($req->line_item_id);

        if($req->user()->cannot('updateBilling', $lineItem->charge->bill))
            abort(403);

        if($req->link_type === 'Invoice') {
            $invoiceRepo = new Repos\InvoiceRepo();
            if($req->action == 'remove_link')
                $lineItem = $invoiceRepo->DetachLineItem($lineItem->line_item_id);
            else
                $lineItem = $invoiceRepo->AttachLineItem($lineItem->line_item_id, $req->link_to_target_id);
        } else if ($req->link_type === 'Pickup Manifest' || $req->link_type === 'Delivery Manifest') {
            $update = [
                'line_item_id' => $lineItem->line_item_id,
                $req->link_type === 'Pickup Manifest' ? 'pickup_manifest_id' : 'delivery_manifest_id' => $req->action === 'remove_link' ? null : $req->link_to_target_id
            ];
            $lineItem = $lineItemRepo->Update($update);
        }

        return json_encode($lineItemRepo->GetById($lineItem->line_item_id));
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

        $addrCollector = new Collectors\AddressCollector();
        $billCollector = new Collectors\BillCollector();
        $packageCollector = new Collectors\PackageCollector();

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

        $bill = $billCollector->Collect($req, $permissions, $pickupAddressId, $deliveryAddressId);

        if($oldBill)
            $bill = $billRepo->Update($bill, $permissions);
        else
            $bill = $billRepo->Insert($bill);

        if((!$req->bill_id && $permissions['createFull']) || (isset($permissions['editBilling']) && $permissions['editBilling'])) {
            $charges = $billCollector->CollectCharges($req, $bill->bill_id);
            $this->handleCharges($charges);
        } else if(!$oldBill && $req->user()->accountUsers()) {
            $charges = $billCollector->CreateChargeForAccountUser($req, $bill->bill_id);
            $this->handleCharges($charges);
        }

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

     /**
      * Handles different charge types.
      * Account simply charges to account, driver creates a chargeback, all others create a payment object instance
      */
    private function handleCharges($charges) {
        $billCollector = new Collectors\BillCollector();
        $chargeRepo = new Repos\ChargeRepo();
        $lineItemRepo = new Repos\LineItemRepo();

        foreach($charges as $charge) {
            if($charge['to_be_deleted']) {
                // If the charge doesn't have an ID, then it was never entered into the database, and neither were its line items
                // It's okay to skip it
                if($charge['charge_id'] === null)
                    continue;
                $chargeRepo->Delete($charge['charge_id']);
            } else {
                if($charge['charge_id'] != null)
                    $chargeId = $chargeRepo->Update($charge)->charge_id;
                else
                    $chargeId = $chargeRepo->Insert($charge)->charge_id;
                $this->handleLineItems($charge['line_items'], $chargeId);
            }
        }
    }

    private function handleLineItems($lineItems, $chargeId) {
        $lineItemRepo = new Repos\LineItemRepo();

        foreach($lineItems as $lineItem) {
            if(isset($lineItem['to_be_deleted']) && $lineItem['to_be_deleted'] === true) {
                // If the line item doesn't have a line_item_id then it was never entered into the database and it's okay to skip it
                if(!$lineItem['line_item_id'])
                    continue;
                $lineItemRepo->Delete($lineItem['line_item_id']);
            } else if($lineItem['line_item_id'])
                $lineItemRepo->UpdateAsBill($lineItem);
            else {
                $lineItem['charge_id'] = $chargeId;
                $lineItemRepo->Insert($lineItem);
            }
        }
    }
}
