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
use Nesk\Puphpeteer\Puppeteer;

use LynX39\LaraPdfMerger\Facades\PdfMerger;

class InvoiceController extends Controller {
    private $storagePath;
    private $folderName;

    public function __construct() {
        $this->middleware('auth');

        $this->storagePath = storage_path() . '/app/public/';
        $this->folderName = 'invoices.' . time();
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
        $invoiceIdArray = explode(',', $invoiceIds);

        foreach($invoiceIdArray as $invoiceId)
            if($req->user()->cannot('update', $invoiceRepo->GetById($invoiceId)))
                abort(403);
            else
                $invoiceRepo->ToggleFinalized($invoiceId);

        return response()->json(['success' => true]);
    }

    public function getAccountsToInvoice(Request $req) {
        if($req->user()->cannot('create', Invoice::class))
            abort(403);

        $invoiceModelFactory = new Invoice\InvoiceModelFactory();
        $model = $invoiceModelFactory->GetGenerateModel($req);

        return json_encode($model);
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

    public function print(Request $req, $invoiceIds) {
        $invoiceIds = explode(',', $invoiceIds);
        if(count($invoiceIds) > 50)
            throw new \Exception('Currently unable to package more than 50 invoices at a time. Please select 50 or fewer and try again. Aplogies for any inconvenience');

        $files = $this->preparePdfs($invoiceIds, $req);

        $pdfMerger = PDFMerger::init();

        foreach($files as $file)
            $pdfMerger->addPDF($file);

        $pdfMerger->merge();

        $this->cleanPdfs($files);

        $fileName = count($files) > 1 ? 'Invoices.' . time() . '.pdf' : array_key_first($files);

        return $pdfMerger->save($fileName, 'inline');
    }

    public function download(Request $req, $invoiceIds) {
        $invoiceIds = explode(',', $invoiceIds);
        if(count($invoiceIds) > 50)
            throw new \Exception('Currently unable to package more than 50 invoices at a time. Please select 50 or fewer and try again. Aplogies for any inconvenience');

        $files = $this->preparePdfs($invoiceIds, $req);

        if(count($files) === 1) {
            return \Response::download($files[array_key_first($files)]);
        } else {
            $zip = new ZipArchive();
            $zipfile = $this->storagePath . $this->folderName . '.zip';
            $zip->open($zipfile, ZipArchive::CREATE);

            foreach($files as $name => $file)
                $zip->addFile($file, $name);

            $zip->close();

            $this->cleanPdfs($files);

            return \Response::download($zipfile);
        }
    }

    public function printPreview(Request $req, $invoiceId) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($invoiceId);

        if($req->user()->cannot('view', $invoice))
            abort(403);

        $amendmentsOnly = $req->amendments_only ?? false;
        $showLineItems = $req->show_line_items ?? false;

        $invoiceModelFactory = new Invoice\InvoiceModelFactory();
        $model = $invoiceModelFactory->GetById($req, $invoiceId);

        return view('invoices.invoice_table', compact('model', 'amendmentsOnly', 'showLineItems'));
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

        $validationRules = ['accounts' => 'required|array|min:1', 'start_date' => 'required|date', 'end_date' => 'required|date|after:' . $req->start_date];
        $validationMessages = ['accounts.required' => 'You must select at least one account to invoice'];
        $this->validate($req, $validationRules, $validationMessages);

        $invoiceRepo = new Repos\InvoiceRepo();

        $startDate = (new \DateTime($req->start_date))->format('Y-m-d');
        $endDate = (new \DateTime($req->end_date))->format('Y-m-d');

        $invoices = $invoiceRepo->Create($req->accounts, $startDate, $endDate);

        DB::commit();
    }

    /**
     * Private functions
     * 
     */
    private function preparePdfs($invoiceIds, $req) {
        $invoiceModelFactory = new Invoice\InvoiceModelFactory();
        $puppeteer = new Puppeteer;

        $globalAmendmentsOnly = $req->amendments_only ? filter_var($req->amendments_only, FILTER_VALIDATE_BOOLEAN) : false;
        $showLineItems = $req->show_line_items ? filter_var($req->show_line_items, FILTER_VALIDATE_BOOLEAN) : false;

        $files = array();
        $path = $this->storagePath . $this->folderName . '/';
        mkdir($path);

        foreach($invoiceIds as $invoiceId) {
            $model = $invoiceModelFactory->GetById($req, $invoiceId);

            if($req->user()->cannot('view', $model->invoice))
                abort(403);

            $fileName = preg_replace('/\s+/', '_', $model->parent->name) . '-' . $model->invoice->invoice_id;
            //check if invoice even has amendments otherwise forcibly set to false
            $amendmentsOnly = isset($model->amendments) ? $globalAmendmentsOnly : false;

            $file = view('invoices.invoice_table', compact('model', 'amendmentsOnly', 'showLineItems'))->render();
            file_put_contents($path . $fileName . '.html', $file);
            $page = $puppeteer->launch()->newPage();
            $page->goto('file://' . $path . $fileName . '.html');
            $page->addStyleTag(['path' => public_path('css/invoice_pdf.css')]);
            $page->pdf([
                'displayHeaderFooter' => true,
                'footerTemplate' => view('invoices.invoice_table_footer')->render(),
                'headerTemplate' => view('invoices.invoice_table_header', compact('model'))->render(),
                'margin' => [
                    'top' => 80,
                    'bottom' => 70,
                    'left' => 30,
                    'right' => 30
                ],
                'path' => $path . $fileName . '.pdf',
                'printBackground' => true,
            ]);

            unlink($path . $fileName . '.html');

            $files[$fileName .'.pdf'] = $path . $fileName . '.pdf';
        }

        return $files;
    }

    private function cleanPdfs($files) {
        $path = $this->storagePath . $this->folderName . '/';

        foreach($files as $file)
            unlink($file);
        rmdir($this->storagePath . $this->folderName);

        return !is_dir($path);
    }
}
