<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use View;
use PDF;
use ZipArchive;
use App\Http\Requests;
use App\Http\Repos;
use App\Http\Models\Invoice;
use App\Http\Services;

class InvoiceController extends Controller {
    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'number';
        $this->maxCount = env('DEFAULT_INVOICE_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_INVOICE_AGE', '6 month');
        $this->class = new \App\Invoice;
    }

    public function download($filename) {
        $path = storage_path() . '/app/public/';
        return response()->download($path . $filename)->deleteFileAfterSend(true);
    }

    public function index() {
        return view('invoices.invoices');
    }

    public function buildTable(Request $req) {
        $invoiceModelFactory = new Invoice\InvoiceModelFactory();
        $model = $invoiceModelFactory->ListAll($req);
        return json_encode($model);
    }

    public function view(Request $req, $id) {
        $invoice_model_factory = new Invoice\InvoiceModelFactory();
        $model = $invoice_model_factory->GetById($id);
        return view('invoices.invoice', compact('model'));
    }

    public function generate(Request $req) {
        // Check permissions
        $invoice_model_factory = new Invoice\InvoiceModelFactory();
        $model = $invoice_model_factory->GetCreateModel($req);
        return view('invoices.invoice-generate', compact('model'));
    }

    public function getOutstandingByAccountId(Request $req) {
        $invoice_repo = new Repos\InvoiceRepo();
        return json_encode($invoice_repo->getOutstandingByAccountId($req->input('account-id')));
    }

    public function delete(Request $req, $id) {
        $invoiceRepo = new Repos\InvoiceRepo();

        $invoiceRepo->delete($id);

        return redirect()->action('InvoiceController@index');
    }

    public function layouts(Request $req, $id) {
        $invoice_model_factory = new Invoice\InvoiceModelFactory();
        $model = $invoice_model_factory->GetLayoutModel($req, $id);
        return view('invoices.layouts', compact('model'));
    }

    public function getAccountsToInvoice(Request $req) {
        $acctRepo = new Repos\AccountRepo();
        $start_date = (new \DateTime($req->start_date))->format('Y-m-d');
        $end_date = (new \DateTime($req->end_date))->format('Y-m-d');
        $accounts = $acctRepo->ListAllWithUninvoicedBillsByInvoiceInterval($req->invoice_interval, $start_date, $end_date);
        return $accounts;
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try{
            $validationRules = [];
            $validationMessages = [];

            if(count($req->checkboxes) < 1) {
                $validationRules = array_merge($validationRules, ['accounts' => 'required']);
                $validationMessages = array_merge($validationMessages, ['accounts.required' => 'You must select at least one account to invoice']);
            }

            $this->validate($req, $validationRules, $validationMessages);

            $invoiceRepo = new Repos\InvoiceRepo();

            $start_date = (new \DateTime($req->start_date))->format('Y-m-d');
            $end_date = (new \DateTime($req->end_date))->format('Y-m-d');

            $accounts = array();
            foreach($req->checkboxes as $account)
                array_push($accounts, $account);

            $invoiceRepo->create($accounts, $start_date, $end_date);

            DB::commit();

            return redirect()->action('InvoiceController@index');
            
        } catch(Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function storeLayout(Request $req) {
        DB::beginTransaction();
        try{
            $accountRepo = new Repos\AccountRepo();
            $invoiceRepo = new Repos\InvoiceRepo();
            
            $accountRepo->UpdateInvoiceComment($req->comment, $req->account_id);
            $invoiceRepo->StoreSortOrder($req, $req->account_id);

            DB::commit();

            return;
        } catch(Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function print($invoice_id) {
        //TODO check if invoice $id exists
        $invoice_model_factory = new Invoice\InvoiceModelFactory();
        $model = $invoice_model_factory->GetById($invoice_id);
        $is_pdf = 1;
        $pdf = PDF::loadView('invoices.invoice_table', compact('model', 'is_pdf'));
        return $pdf->stream($model->parent->name . '.' . $model->invoice->date . '.pdf');
    }

    public function printMass(Request $req) {
        $storagepath = storage_path() . '/app/public/';
        $foldername = 'invoices.' . time();
        mkdir($storagepath . $foldername);
        $path = $storagepath . $foldername . '/';
        $files = array();

        $zip = new ZipArchive();
        $zipfile = $storagepath . $foldername . '.zip';
        $zip->open($zipfile, ZipArchive::CREATE);

        $toBeUnlinked =  array();

        foreach($req->checkboxes as $invoice_id => $value) {
            $invoiceModelFactory = new Invoice\InvoiceModelFactory();
            $model = $invoiceModelFactory->GetById($invoice_id);
            $filename = $model->parent->name . '-' . $model->invoice->invoice_id;
            $is_pdf = 1;
            $pdf = PDF::loadView('invoices.invoice_table', compact('model', 'is_pdf'));
            $pdf->save($path . $filename, $filename);
            $zip->addFile($path . $filename, $filename);
            $toBeUnlinked[$invoice_id] = $path . $filename;
        }

        $zip->close();

        foreach($toBeUnlinked as $file)
            unlink($file);
        rmdir($storagepath . $foldername);

        return $foldername . '.zip';
    }
}
