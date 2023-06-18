<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use View;
use ZipArchive;
use App\Http\Collectors;
use App\Http\Requests;
use App\Http\Repos;
use App\Http\Models\Invoice;
use App\Http\Services;
use Illuminate\Support\Facades\Storage;

use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;


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
        if($user->can('invoices.view.*.*') || $user->can('invoices.edit.*.*'))
            $invoices = $invoiceRepo->ListAll(null);
        else if($user->accountUsers && $user->hasAnyPermission('invoices.view.my', 'invoices.view.children'))
            $invoices = $invoiceRepo->ListAll($accountRepo->GetMyAccountIds($user, $user->can('invoices.view.children')));

        return json_encode($invoices);
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
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoiceIdArray = json_decode($invoiceIds);

        foreach($invoiceIdArray as $invoiceId)
            if($req->user()->cannot('update', $invoiceRepo->GetById($invoiceId)))
                abort(403);
            else
                $invoiceRepo->ToggleFinalized($invoiceId);

        return response()->json(['success' => true]);
    }

    public function getModel(Request $req, $invoiceId = null) {
        $invoiceModelFactory = new Invoice\InvoiceModelFactory();
        if($invoiceId) {
            if(!\App\Invoice::where('invoice_id', $invoiceId)->exists())
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

        $invoiceModelFactory = new Invoice\InvoiceModelFactory();
        $model = $invoiceModelFactory->GetGenerateModel($req);

        return json_encode($model);
    }

    public function print(Request $req, $invoiceIds) {
        $invoiceIds = explode(',', $invoiceIds);
        if(count($invoiceIds) > 50)
            abort(413, 'Currently unable to package more than 50 invoices at a time. Please select 50 or fewer and try again. Aplogies for any inconvenience');

        $files = $this->preparePdfs($invoiceIds, $req);

        $pdfMerger = PDFMerger::init();

        foreach($files as $file)
            $pdfMerger->addPDF($file);

        $pdfMerger->merge();

        $this->cleanPdfs($files);

        $fileName = count($files) > 1 ? 'Invoices.' . time() . '.pdf' : array_key_first($files);

        return response($pdfMerger->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename=' . $fileName);
    }

    public function download(Request $req, $invoiceIds) {
        $invoiceIds = explode(',', $invoiceIds);
        if(count($invoiceIds) > 50)
            abort(413, 'Currently unable to package more than 50 invoices at a time. Please select 50 or fewer and try again. Apologies for any inconvenience');

        $files = $this->preparePdfs($invoiceIds, $req);

        $zipArchive = new ZipArchive();
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, 'zip');
        $zipArchive->open($tempFile, ZipArchive::CREATE);

        foreach($files as $name => $file)
            $zipArchive->addFile($file, $name);

        $zipArchive->close();

        $this->cleanPdfs($files);

        return response()->download($tempFile, 'invoices-' . time() . '.zip')->deleteFileAfterSend(true);
    }

    public function printPreview(Request $req, $invoiceId) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($invoiceId);

        if($req->user()->cannot('view', $invoice))
            abort(403);

        $amendmentsOnly = $req->amendments_only ?? false;
        $hideOutstandingInvoices = $req->hideOutstandingInvoices ?? false;
        $showLineItems = $req->show_line_items ?? false;
        $showPickupAndDeliveryAddress = $req->show_pickup_and_delivery_address ?? false;

        $invoiceModelFactory = new Invoice\InvoiceModelFactory();
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
        $invoiceModelFactory = new Invoice\InvoiceModelFactory();

        $globalAmendmentsOnly = isset($req->amendments_only) ? filter_var($req->amendments_only, FILTER_VALIDATE_BOOLEAN) : false;

        $files = array();
        $path = $this->storagePath;
        mkdir($path, 0777, true);

        foreach($invoiceIds as $invoiceId) {
            $model = $invoiceModelFactory->GetById($req, $invoiceId);
            if(isset($invoice->account_id))
                $account = $accountRepo->GetById($model->parent->account_id);
            else {
                $account = new \stdClass;
                $account->show_invoice_line_items = true;
                $account->show_pickup_and_delivery_address = true;
            }

            if($req->user()->cannot('view', $model->invoice))
                abort(403);

            $fileName = trim($model->parent->name) . '-' . $model->invoice->invoice_id;
            $fileName = preg_replace('/\s+/', '_', $fileName);
            $fileName = preg_replace('/[&.\/\\:*?"<>| ]/', '', $fileName);
            //check if invoice even has amendments otherwise forcibly set to false
            $amendmentsOnly = isset($model->amendments) ? $globalAmendmentsOnly : false;
            $hideOutstandingInvoices = isset($req->hide_outstanding_invoices) ? filter_var($req->hide_outstanding_invoices, FILTER_VALIDATE_BOOLEAN) : true;
            $showLineItems = isset($req->show_line_items) ? filter_var($req->show_line_items, FILTER_VALIDATE_BOOLEAN) : $account->show_invoice_line_items;
            $showPickupAndDeliveryAddress = isset($req->show_pickup_and_delivery_address) ? filter_var($req->show_pickup_and_delivery_address) : $account->show_pickup_and_delivery_address;

            $puppeteerScript = resource_path('assets/js/puppeteer/phpPuppeteer.js');

            $inputFile = $this->storagePath . $fileName . '.html';
            $outputFile = $this->storagePath . $fileName . '.pdf';
            $headerFile = $this->storagePath . $fileName . '-header.html';
            $footerFile = $this->storagePath . $fileName . '-footer.html';
            file_put_contents($inputFile, view('invoices.invoice_table', compact('model', 'amendmentsOnly', 'showLineItems', 'showPickupAndDeliveryAddress', 'hideOutstandingInvoices'))->render());
            file_put_contents($headerFile, view('invoices.invoice_table_header', compact('model'))->render());
            file_put_contents($footerFile, view('invoices.invoice_table_footer')->render());

            $options = json_encode([
                'displayHeaderFooter' => true,
                'margin' => [
                    'top' => 80,
                    'bottom' => 70,
                    'left' => 30,
                    'right' => 30
                ],
                'path' => $outputFile,
                'printBackground' => true,
            ], JSON_UNESCAPED_SLASHES);

            $command = 'node ' . $puppeteerScript . ' --file file:' . $inputFile;
            $command .= ' --header ' . $headerFile;
            $command .= ' --footer ' . $footerFile;
            $command .= ' --stylesheet ' . public_path('css/invoice_pdf.css');
            $command .= ' --pdfOptions ' . preg_replace('/\s+/', '', json_encode($options));

            exec($command, $output, $returnCode);
            if($returnCode != 0 || !file_exists($outputFile))
                dd($returnCode, $output, $command);

            unlink($inputFile);
            unlink($headerFile);
            unlink($footerFile);

            $files[$fileName . '.pdf'] = $outputFile;
        }

        return $files;
    }

    private function cleanPdfs($files) {
        foreach($files as $file)
            unlink($file);
        rmdir($this->storagePath);

        return !is_dir($this->storagePath);
    }
}
