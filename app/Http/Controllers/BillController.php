<?php

namespace App\Http\Controllers;

// Events
use App\Events\BillCreated;
use App\Events\BillUpdated;
// Classes
use App\Http\Collectors;
use App\Http\Repos;
use App\Http\Models\Permission;
use App\Http\Resources\BillPrintResource;
use App\Models\Bill;
use App\Models\LineItem;
use App\Services\PDFService;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class BillController extends Controller {
    public function __construct() {
        $this->middleware('auth');

        $this->storagePath = storage_path() . '/bills/' . (new \DateTime())->format('Y_m_d_H-i-s/');
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

        $chargeModelFactory = new \App\Http\Models\Bill\ChargeModelFactory();
        $charges = $chargeModelFactory->GenerateCharges($req);

        return json_encode($charges);
    }

    public function getModel(Request $req, $billId = null) {
        $billModelFactory = new \App\Http\Models\Bill\BillModelFactory();
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
        } else if(isset($req->copy_from)) {
            $billRepo = new Repos\BillRepo();
            $templateBill = $billRepo->GetById($req->copy_from);
            if(!$templateBill)
                abort(404);
            if($req->user()->cannot('viewBasic', $templateBill))
                abort(403);

            $permissions = $permissionModelFactory->GetBillPermissions($req->user(), $templateBill);
            $model = $billModelFactory->GetCopyModel($req, $permissions);
        } else {
            if($req->user()->cannot('createBasic', Bill::class))
                abort(403);
            $permissions = $permissionModelFactory->GetBillPermissions($req->user());
            $model = $billModelFactory->GetCreateModel($req, $permissions);
        }

        return json_encode($model);
    }

    public function index(Request $req) {
        $user = $req->user();
        if($user->cannot('viewAny', Bill::class))
            abort(403);

        $billModelFactory = new \App\Http\Models\Bill\BillModelFactory();

        $bills = $billModelFactory->BuildTable($req);

        $customFieldNames = [];
        if($req->user()->accountUsers()) {
            $accountRepo = new Repos\AccountRepo();
            foreach($req->user()->accountUsers as $accountUser)
                $customFieldNames[] = $accountRepo->GetById($accountUser->account_id)->custom_field;
        }

        $queryRepo = new Repos\QueryRepo();
        $queries = $queryRepo->GetByTable('bills');

        return response()->json([
            'success' => true,
            'custom_field_name' => sizeof($customFieldNames) > 0 ? implode(',', array_unique($customFieldNames)) : 'Custom Tracking Field',
            'data' => $bills,
            'queries' => $queries
        ]);
    }

    public function manageLineItemLinks(Request $req) {
        $lineItemRepo = new Repos\LineItemRepo();

        $lineItem = $lineItemRepo->GetById($req->line_item_id);

        if($req->user()->cannot('updateBilling', $lineItem->charge->bill))
            abort(403);

        if($req->link_type === 'Invoice') {
            $invoiceRepo = new Repos\InvoiceRepo();
            if($req->action == 'remove_link') {
                $invoice = $invoiceRepo->GetById($lineItem->invoice_id);
                if($invoice->finalized)
                    abort(422, 'Unable to detach line item from finalized invoice');
                $lineItem = $invoiceRepo->DetachLineItem($lineItem->line_item_id);
            }
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
        $bill = Bill::findOrFail($billId);
        if(Auth::user()->cannot('viewBasic', $bill))
            abort(403);

        $billHtml = [];
        $PDFService = new PDFService();

        $showCharges = $req->has('showCharges') && Auth::user()->can('viewCharges', $bill);

        $bills = BillPrintResource::collection([$bill])->response()->getData(true)['data'];

        $billHtml[] = [
            'body' => view('bills.bill_print_view', compact('bills', 'showCharges')),
            'footer' => view('bills.bill_footer')
        ];

        $fileName = 'bill_' . $bill->bill_id . '_' . preg_replace('/\s+|:/', '_', $bill->time_pickup_scheduled);

        return response($PDFService->create($fileName, $billHtml, ['landscape' => true, 'margins' => [8, 10, 20, 10]]))
            ->header('Content-Type', 'application/pdf');
    }

    public function store(Request $req) {
        $billRepo = new Repos\BillRepo();
        $oldBill = $billRepo->getById($req->bill_id);
        $warnings = [];

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

        $addressRepo = new Repos\AddressRepo();
        $packageRepo = new Repos\PackageRepo();
        $chargebackRepo = new Repos\ChargebackRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $addrCollector = new Collectors\AddressCollector();
        $billCollector = new Collectors\BillCollector();
        $packageCollector = new Collectors\PackageCollector();

        $pickupAddress = $addrCollector->collectWithPrefix($req, 'pickup_address', $oldBill ? $oldBill->pickup_address_id : null);
        $deliveryAddress = $addrCollector->collectWithPrefix($req, 'delivery_address', $oldBill ? $oldBill->delivery_address_id : null);

        DB::beginTransaction();

        if ($oldBill) {
            $pickupAddressId = $addressRepo->UpdateMinimal($pickupAddress)->address_id;
            $deliveryAddressId = $addressRepo->UpdateMinimal($deliveryAddress)->address_id;
        } else {
            $pickupAddressId = $addressRepo->InsertMinimal($pickupAddress)->address_id;
            $deliveryAddressId = $addressRepo->InsertMinimal($deliveryAddress)->address_id;
        }

        $bill = $billCollector->Collect($req, $permissions, $pickupAddressId, $deliveryAddressId);

        if($oldBill)
            $bill = $billRepo->Update($bill, $permissions);
        else
            $bill = $billRepo->Insert($bill);

        if((!$req->bill_id && $permissions['createFull']) || (isset($permissions['editBilling']) && $permissions['editBilling'])) {
            $charges = $billCollector->CollectCharges($req, $bill->bill_id);
            $warnings = $this->handleCharges($charges, $warnings);
        } else if(!$oldBill && $req->user()->accountUsers()) {
            $charges = $billCollector->CreateChargeForAccountUser($req, $bill->bill_id);
            $warnings = $this->handleCharges($charges, $warnings);
        }

        DB::commit();

        if($oldBill)
            event(new BillUpdated($bill));
        else
            event(new BillCreated($bill));

        return response()->json([
            'success' => true,
            'id' => $bill->bill_id,
            'updated_at' => $bill->updated_at,
            'warnings' => $warnings
        ]);
    }

    public function template(Request $req, $billId) {
        $billRepo = new Repos\BillRepo();

        $bill = $billRepo->GetByid($billId);

        if($req->user()->cannot('view', $billId) && $req->user()->cannot('createBasic', Bill::class))
            abort(403);

        $isTemplate = $billRepo->toggleTemplate($billId)->is_template;

        return response()->json([
            'success' => true,
            'is_template' => $isTemplate
        ]);
    }

    /**
     * Private functions
     */

     /**
      * Handles different charge types.
      * Account simply charges to account, driver creates a chargeback, all others create a payment object instance
      */
    private function handleCharges($charges, $warnings) {
        $chargeRepo = new Repos\ChargeRepo();
        $warnings = null;

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
                $warnings = $this->handleLineItems($charge['line_items'], $chargeId);
            }
        }

        return $warnings;
    }

    private function handleLineItems($lineItems, $chargeId) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $lineItemRepo = new Repos\LineItemRepo();
        $invoicesToRegather = [];
        $warnings = [];

        foreach($lineItems as $lineItem) {
            if(isset($lineItem['to_be_deleted']) && $lineItem['to_be_deleted'] === true) {
                // If the line item doesn't have a line_item_id then it was never entered into the database and it's okay to skip it
                if(!isset($lineItem['line_item_id']))
                    continue;
                $lineItemRepo->Delete($lineItem['line_item_id']);
            }
            else if($lineItem['line_item_id']) {
                $dbLineItem = LineItem::find($lineItem['line_item_id']);
                if($dbLineItem->invoice && $dbLineItem->invoice->invoice_id != null) {
                    if(!$dbLineItem->invoice->finalized) {
                        $lineItemRepo->updateAsBill($lineItem);
                        $invoicesToRegather[] = $dbLineItem->invoice;
                    } else
                        $warnings[] = 'Unable to modify line item ' . $dbLineItem->line_item_id . ': Attached invoice has been finalized';
                } else
                    $lineItemRepo->updateAsBill($lineItem);
            }
            else {
                $lineItem['charge_id'] = $chargeId;
                $lineItemRepo->Insert($lineItem);
            }
        }

        // Regather invoices after all changes are made to allow for deletion of non-processed LineItems
        foreach($invoicesToRegather as $invoice)
            $invoiceRepo->RegatherInvoice($invoice);

        return $warnings;
    }
}
