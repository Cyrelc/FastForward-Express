<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use View;
use ZipArchive;
use App\Http\Collectors;
use App\Http\Models;
use App\Http\Requests;
use App\Http\Repos;
use App\Http\Resources\BillPrintResource;
use App\Http\Services;
use App\Models\Invoice;
use App\Services\PDFService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Browsershot\Browsershot;

use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
use App\Jobs\SendInvoiceFinalizedEmail;

class InvoiceController extends Controller {
    private $storagePath;

    public function __construct() {
        $this->middleware('auth');

        $this->storagePath = storage_path() . '/invoices/' . (new \DateTime())->format('Y_m_d_H-i-s/');
    }

    public function buildTable(Request $req) {
        $user = $req->user();
        if($user->cannot('viewAny', Invoice::class))
            abort(403);
        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $queryRepo = new Repos\QueryRepo();

        if($user->can('invoices.view.*.*') || $user->can('invoices.edit.*.*'))
            $invoices = $invoiceRepo->ListAll(null);
        else if($user->accountUsers && $user->hasAnyPermission('invoices.view.my', 'invoices.view.children'))
            $invoices = $invoiceRepo->ListAll($accountRepo->GetMyAccountIds($user, $user->can('invoices.view.children')));

        $queries = $queryRepo->GetByTable('invoices');

        return response()->json([
            'success'=> true,
            'data'=> $invoices,
            'queries' => $queries
        ]);
    }

    public function createFromCharge(Request $req) {
        if($req->user()->cannot('create', Invoice::class))
            abort(403);

        $billRepo = new Repos\BillRepo();
        $chargeRepo = new Repos\ChargeRepo();

        $charge = $chargeRepo->GetById($req->charge_id);
        $bill = $billRepo->GetById($charge->bill_id);

        if($req->user()->cannot('updateBilling', $bill))
            abort(403);

        DB::beginTransaction();

        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->CreateFromCharge($charge->charge_id);

        DB::commit();

        return json_encode($invoice);
    }

    public function delete(Request $req, $invoiceId) {
        DB::beginTransaction();

        $invoiceRepo = new Repos\InvoiceRepo();
        if($req->user()->cannot('delete', $invoiceRepo->GetById($invoiceId)))
            abort(403);

        $invoiceRepo->delete($invoiceId);
        DB::commit();

        return response()->json([
            'success' => true
        ]);
    }

    public function finalize(Request $req, $invoiceIds) {
        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $userRepo = new Repos\UserRepo();

        $invoiceIdArray = json_decode($invoiceIds);
        if(!is_array($invoiceIdArray))
            $invoiceIdArray = array($invoiceIdArray);

        $invoices = [];

        foreach($invoiceIdArray as $invoiceId)
            if($req->user()->cannot('update', $invoiceRepo->GetById($invoiceId)))
                abort(403);
            else {
                $invoice = $invoiceRepo->ToggleFinalized($invoiceId);
                $invoices[$invoice->invoice_id] = ['finalized' => $invoice->finalized];
                if($invoice->finalized && $invoice->account_id) {
                    $account = $accountRepo->GetById($invoice->account_id);
                    $billingUsers = $userRepo->GetAccountUsersWithEmailRole($invoice->account_id, 'Billing');
                    if($account->send_email_invoices && $billingUsers && count($billingUsers) > 0) {
                        SendInvoiceFinalizedEmail::dispatch($billingUsers, $invoice)->delay(now()->addHours(2));
                    }
                }
            }

        return response()->json(['success' => true, 'invoices' => $invoices]);
    }

    public function getModel(Request $req, $invoiceId = null) {
        $invoiceModelFactory = new Models\Invoice\InvoiceModelFactory();
        if($invoiceId) {
            if(!Invoice::where('invoice_id', $invoiceId)->exists())
                abort(404);

            $model = $invoiceModelFactory->GetById($req, $invoiceId);

            if($req->user()->cannot('view', $model->invoice))
                abort(403);
        } else {
            if($req->user()->cannot('create', Invoice::class))
                abort(403);

            $model = $invoiceModelFactory->GetCreateModel();
        }

        return json_encode($model);
    }

    public function getOutstandingByAccountId(Request $req) {
        $invoiceRepo = new Repos\InvoiceRepo();

        $invoices = $invoiceRepo->GetOutstandingByAccountId($req->input('account_id'));
        foreach($invoices as $invoice)
            if($req->user()->cannot('view', $invoice))
                abort(403);

        return json_encode($invoices);
    }

    public function getUninvoiced(Request $req) {
        if($req->user()->cannot('create', Invoice::class))
            abort(403);

        $invoiceModelFactory = new Models\Invoice\InvoiceModelFactory();
        $model = $invoiceModelFactory->GetGenerateModel($req);

        return json_encode($model);
    }

    public function print(Request $req, $invoiceIds) {
        $invoiceIds = explode(',', $invoiceIds);
        if(count($invoiceIds) > 50)
            abort(413, 'Currently unable to package more than 50 invoices at a time. Please select 50 or fewer and try again. Apologies for any inconvenience');

        $PDFService = new PDFService();
        $files = $this->preparePdfs($invoiceIds, $req);

        $fileName = count($files) > 1 ? 'Invoices.' . time() . '.pdf' : 'Invoice_' . $invoiceIds[0] . '.pdf';

        return response($PDFService->create($fileName, $files))
            ->header('Content-Type', 'application/pdf');
    }

    public function printBills(Request $req, $invoiceId) {
        $invoice = Invoice::findOrFail($invoiceId);
        if(Auth::user()->cannot('view', $invoice))
            abort(403);

        $billHtml = [];
        $PDFService = new PDFService();

        $showCharges = $req->has('showCharges');

        $bills = BillPrintResource::collection($invoice->bills())->response()->getData(true)['data'];

        $billHtml[] = [
            'body' => view('bills.bill_print_view', compact('bills', 'showCharges')),
            'footer' => view('bills.bill_footer')
        ];

        $fileName = 'invoice_' . $invoiceId . '_bills.pdf';

        return response($PDFService->create($fileName, $billHtml, ['landscape' => true, 'margins' => [8, 10, 20, 10]]))
            ->header('Content-Type', 'application/pdf');
    }

    // public function download(Request $req, $invoiceIds) {
    //     $invoiceIds = explode(',', $invoiceIds);
    //     if(count($invoiceIds) > 50)
    //         abort(413, 'Currently unable to package more than 50 invoices at a time. Please select 50 or fewer and try again. Apologies for any inconvenience');

    //     $files = $this->preparePdfs($invoiceIds, $req);

    //     $zipArchive = new ZipArchive();
    //     $tempDir = sys_get_temp_dir();
    //     $tempFile = tempnam($tempDir, 'zip');
    //     $zipArchive->open($tempFile, ZipArchive::CREATE);

    //     foreach($files as $name => $file)
    //         $zipArchive->addFile($file, $name);

    //     $zipArchive->close();

    //     $this->cleanPdfs($files);

    //     return response()->download($tempFile, 'invoices-' . time() . '.zip')->deleteFileAfterSend(true);
    // }

    public function printPreview(Request $req, $invoiceId) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($invoiceId);

        if($req->user()->cannot('view', $invoice))
            abort(403);

        $amendmentsOnly = $req->amendments_only ?? false;
        $hideOutstandingInvoices = $req->hideOutstandingInvoices ?? false;
        $showLineItems = $req->show_line_items ?? false;
        $showPickupAndDeliveryAddress = $req->show_pickup_and_delivery_address ?? false;

        $invoiceModelFactory = new Models\Invoice\InvoiceModelFactory();
        $model = $invoiceModelFactory->GetById($req, $invoiceId);

        return view('invoices.invoice_table', compact('model', 'amendmentsOnly', 'showLineItems', 'hideOutstandingInvoices', 'showPickupAndDeliveryAddress'));
    }

    public function regather(Request $req, $invoiceId) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $lineItemRepo = new Repos\LineItemRepo();

        $invoice = $invoiceRepo->GetById($invoiceId);
        if($req->user()->cannot('update', $invoice))
            abort(403);

        $count = $invoiceRepo->RegatherInvoice($invoice);

        return ['success' => true, 'count' => $count];
    }

    public function store(Request $req) {
        if($req->user()->cannot('create', Invoice::class))
            abort(403);

        DB::beginTransaction();

        $validationRules = [
            'accounts' => 'required_if:prepaid,[]|array|min:1',
            'prepaid' => 'required_if:accounts,[]|array|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:' . $req->start_date
        ];

        $validationMessages = ['accounts.required_if' => 'You must select at least one item to invoice'];
        $this->validate($req, $validationRules, $validationMessages);

        $invoiceRepo = new Repos\InvoiceRepo();

        $startDate = (new \DateTime($req->start_date))->format('Y-m-d');
        $endDate = (new \DateTime($req->end_date))->format('Y-m-d');

        if($req->accounts)
            $accountInvoices = $invoiceRepo->CreateForAccounts($req->accounts, $startDate, $endDate);

        if($req->prepaid)
            foreach($req->prepaid as $chargeId)
                $invoiceRepo->CreateFromCharge($chargeId);

        DB::commit();
    }

    /**
     * Private functions
     * 
     */
    private function preparePdfs($invoiceIds, $req) {
        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();

        $invoiceModelFactory = new Models\Invoice\InvoiceModelFactory();

        $globalAmendmentsOnly = isset($req->amendments_only) ? filter_var($req->amendments_only, FILTER_VALIDATE_BOOLEAN) : false;

        $files = array();

        foreach($invoiceIds as $invoiceId) {
            $invoice = $invoiceRepo->GetById($invoiceId);
            $model = $invoiceModelFactory->GetById($req, $invoiceId);
            if(isset($invoice->account_id)) {
                $account = $accountRepo->GetById($model->parent->account_id);
                $account->hide_outstanding_invoices = isset($req->hide_outstanding_invoices) ? filter_var($req->hide_outstanding_invoices, FILTER_VALIDATE_BOOLEAN) : false;
            } else {
                $account = new \stdClass;
                $account->show_invoice_line_items = true;
                $account->show_pickup_and_delivery_address = true;
                $account->hide_outstanding_invoices = true;
            }

            if($req->user()->cannot('view', $model->invoice))
                abort(403);

            //check if invoice even has amendments otherwise forcibly set to false
            $amendmentsOnly = isset($model->amendments) ? $globalAmendmentsOnly : false;
            $hideOutstandingInvoices = $account->hide_outstanding_invoices;
            $showLineItems = isset($req->show_line_items) ? filter_var($req->show_line_items, FILTER_VALIDATE_BOOLEAN) : $account->show_invoice_line_items;
            $showPickupAndDeliveryAddress = isset($req->show_pickup_and_delivery_address) ? filter_var($req->show_pickup_and_delivery_address) : $account->show_pickup_and_delivery_address;

            $files[] = [
                'body' => view('invoices.invoice_table', [
                    'model' => $model,
                    'amendmentsOnly' => $amendmentsOnly,
                    'showLineItems' => $showLineItems,
                    'showPickupAndDeliveryAddress' => $showPickupAndDeliveryAddress,
                    'hideOutstandingInvoices' => $hideOutstandingInvoices
                ])->render(),
                'footer' => view('invoices.invoice_table_footer')->render(),
                'header' => view('invoices.invoice_table_header', ['model' => $model])->render(),
            ];
        }

        return $files;
    }
}
