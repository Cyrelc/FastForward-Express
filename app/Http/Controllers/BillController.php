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
use Nesk\Puphpeteer\Puppeteer;

use DB;
use Illuminate\Http\Request;
use Validator;

class BillController extends Controller {
    public function __construct() {
        $this->middleware('auth');

        $this->storagePath = storage_path() . '/app/public/';
        $this->folderName = 'bills.' . time();
    }

    public function buildTable(Request $req) {
        $user = $req->user();
        if($user->cannot('viewAny', Bill::class))
            abort(403);

        $billModelFactory = new Bill\BillModelFactory();
        $bills = $billModelFactory->BuildTable($req);

        return json_encode($bills);
    }

    public function copyBill(Request $req, $billId) {
        $billRepo = new Repos\BillRepo();
        $bill = $billRepo->GetById($billId);

        if($req->user()->cannot('copyBill', $bill))
            abort(403);

        DB::beginTransaction();

        $bill = $billRepo->CopyBill($req->user(), $billId);

        DB::commit();
        event(new BillCreated($bill));

        return response()->json([
            'bill_id' => $bill->bill_id,
            'success' => true
        ]);
    }

    public function delete(Request $req, $billId) {
        $billRepo = new Repos\BillRepo();
        $bill = $billRepo->GetById($billId);
        if($req->user()->cannot('delete', $bill))
            abort(403);

        DB::beginTransaction();

        $billRepo->Delete($billId);

        DB::commit();
        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Generates charges based on location, size and weight of delivery, and ratesheet.
     * Otherwise recognizes that the system does not have enough information to do so
     * @param pickup_location (lat, lng)
     * @param delivery_location (lat, lng)
     * @param delivery_type_id
     * @param packages (weight, size)
     * @param package_is_minimum
     * @param package_is_pallet
     * @param time_pickup_scheduled
     * @param time_delivery_scheduled
     * @param charge_account_id (optional)
     * @param ratesheet_id (optional)
     * 
     * @return charges an array of charges applicable to the bill
     */
    public function generateCharges(Request $req) {
        $chargeValidation = new \App\Http\Validation\ChargeValidationRules();

        $temp = $chargeValidation->GenerateChargesValidationRules($req);
        $this->validate($req, $temp['rules'], $temp['messages']);

        $chargeModelFactory = new Bill\ChargeModelFactory();
        $charges = $chargeModelFactory->GenerateCharges($req);

        return json_encode($charges);
    }

    public function getModel(Request $req, $billId = null) {
        $billModelFactory = new Bill\BillModelFactory();
        $permissionModelFactory = new Permission\PermissionModelFactory();
        if($billId) {
            $billRepo = new Repos\BillRepo();

            $bill = $billRepo->GetById($billId);

            if($bill === null)
                abort(404);

            if($req->user()->cannot('viewBasic', $bill))
                abort(403);

            $accountRepo = new Repos\AccountRepo();

            $permissions = $permissionModelFactory->GetBillPermissions($req->user(), $bill);

            $model = $billModelFactory->GetEditModel($req, $billId, $permissions);
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

    public function print(Request $req, $billId) {
        $billRepo = new Repos\BillRepo();

        $bill = $billRepo->GetById($billId);

        if($req->user()->cannot('viewBasic', $bill))
            abort(403);

        $puppeteer = new Puppeteer;
        $billModelFactory = new Bill\BillModelFactory();
        $permissionModelFactory = new Permission\PermissionModelFactory();

        $permissions = $permissionModelFactory->GetBillPermissions($req->user(), $bill);

        $model = $billModelFactory->GetEditModel($req, $bill->bill_id, $permissions);

        $path = $this->storagePath . $this->folderName . '/';
        $fileName = 'bill_' . $model->bill->bill_id . '_' . preg_replace('/\s+|:/', '_', $model->bill->time_pickup_scheduled);
        mkdir($path);

        $file = view('bills.bill_print_view', compact('model'))->render();
        file_put_contents($path . $fileName . '.html', $file);
        $page = $puppeteer->launch()->newPage();
        $page->goto('file://' . $path . $fileName . '.html');
        // $page->addStyleTag(['path' => public_path('css/bill_pdf.css')]);
        $page->pdf([
            'displayHeaderFooter' => true,
            'footerTemplate' => view('bills.bill_footer')->render(),
            'headerTemplate' => view('bills.bill_header', compact('model'))->render(),
            'margin' => [
                'top' => 80,
                'bottom' => 70,
                'left' => 30,
                'right' => 30
            ],
            'path' => $path . $fileName . '.pdf',
        ]);

        unlink($path . $fileName . '.html');

        return response()->file($path . $fileName . '.pdf');
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
        $invoiceRepo = new Repos\InvoiceRepo();
        $lineItemRepo = new Repos\LineItemRepo();

        foreach($lineItems as $lineItem) {
            if(isset($lineItem['to_be_deleted']) && $lineItem['to_be_deleted'] === true) {
                // If the line item doesn't have a line_item_id then it was never entered into the database and it's okay to skip it
                if(!$lineItem['line_item_id'])
                    continue;
                $lineItemRepo->Delete($lineItem['line_item_id']);
            } else if($lineItem['line_item_id']) {
                $invoiceId = $lineItemRepo->GetById($lineItem['line_item_id'])->invoice_id;
                if($invoiceId != null) {
                    $invoice = $invoiceRepo->GetById($invoiceId);
                    if(!$invoice->is_finalized) {
                        $lineItemRepo->UpdateAsBill($lineItem);
                        $invoiceRepo->RegatherInvoice($invoice);
                    } else
                        abort(422, 'Unable to modify price on line item: Attached invoice has been finalized');
                } else
                    $lineItemRepo->UpdateAsBill($lineItem);
            }
            else {
                $lineItem['charge_id'] = $chargeId;
                $lineItemRepo->Insert($lineItem);
            }
        }
    }
}
