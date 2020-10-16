<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use View;
use PDF;
use ZipArchive;
use App\Http\Collectors;
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

    public function createAmendment(Request $req) {
        DB::beginTransaction();
        $amendmentValidation = new \App\Http\Validation\AmendmentValidationRules();
        $validationRules = $amendmentValidation->GetValidationRules($req);

        $this->validate($req, $validationRules['rules'], $validationRules['messages']);

        $amendmentCollector = new Collectors\AmendmentCollector();
        $amendment = $amendmentCollector->collect($req);

        $invoiceRepo = new Repos\InvoiceRepo();
        if($diff->days < (int)config('ffe_config.days_invoice_editable'))
            $invoiceRepo->InsertAmendment($amendment);
        else
            throw new \Exception('Invoices older than ' . config('ffe_config.days_invoice_editable') . ' days old can no longer be edited.');

        DB::commit();
    }

    public function deleteAmendment($amendmentId) {
        DB::beginTransaction();
        $invoiceRepo = new Repos\InvoiceRepo();

        $amendment = $invoiceRepo->GetAmendmentById($amendmentId);
        $invoice = $invoiceRepo->GetById($amendment->invoice_id);

        $billEndDate = new \DateTime($invoice->bill_end_date);
        $currentDate = new \DateTime('now');
        $diff = $currentDate->diff($billEndDate, true);
        if($diff->days < (int)config('ffe_config.days_invoice_editable'))
            $invoiceRepo->DeleteAmendment($amendmentId);
        else
            throw new \Exception('Invoices older than ' . config('ffe_config.days_invoice_editable') . ' days old can no longer be edited.');

        DB::commit();
    }

    public function download($filename) {
        $path = storage_path() . '/app/public/';
        return response()->download($path . $filename)->deleteFileAfterSend(true);
    }

    public function buildTable() {
        $invoiceRepo = new Repos\InvoiceRepo();
        return json_encode($invoiceRepo->ListAll());
    }

    public function finalize($invoiceIds) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoiceIdArray = explode(',', $invoiceIds);
        foreach($invoiceIdArray as $invoiceId)
            $invoiceRepo->toggleFinalized($invoiceId);

        return response()->json(['success' => true]);
    }

    public function getModel(Request $req, $id) {
        $invoice_model_factory = new Invoice\InvoiceModelFactory();
        $model = $invoice_model_factory->GetById($id);
        return json_encode($model);
    }

    public function generate(Request $req) {
        // Check permissions
        $invoice_model_factory = new Invoice\InvoiceModelFactory();
        $model = $invoice_model_factory->GetCreateModel($req);
        return view('invoices.invoice-generate', compact('model'));
    }

    public function getOutstandingByAccountId(Request $req) {
        $invoice_repo = new Repos\InvoiceRepo();
        return json_encode($invoice_repo->getOutstandingByAccountId($req->input('account_id')));
    }

    public function delete(Request $req, $id) {
        DB::beginTransaction();
        try{
            $invoiceRepo = new Repos\InvoiceRepo();

            $invoiceRepo->delete($id);
            DB::commit();
            return response()->json([
                'success' => true
            ]);
        } catch(Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getAccountsToInvoice(Request $req) {
        $acctRepo = new Repos\AccountRepo();
        $startDate = (new \DateTime($req->start_date))->format('Y-m-d');
        $endDate = (new \DateTime($req->end_date))->format('Y-m-d');
        $accounts = $acctRepo->ListAllWithUninvoicedBillsByInvoiceInterval($req->invoice_intervals, $startDate, $endDate);
        return $accounts;
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try{
            $validationRules = ['accounts' => 'required|array|min:1', 'start_date' => 'required|date', 'end_date' => 'required|date|after:' . $req->start_date];
            $validationMessages = ['accounts.required' => 'You must select at least one account to invoice'];

            $this->validate($req, $validationRules, $validationMessages);

            $invoiceRepo = new Repos\InvoiceRepo();

            $start_date = (new \DateTime($req->start_date))->format('Y-m-d');
            $end_date = (new \DateTime($req->end_date))->format('Y-m-d');

            $invoiceRepo->create($req->accounts, $start_date, $end_date);

            DB::commit();
        } catch(Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function print(Request $req, $invoice_id) {
        //TODO check if invoice $id exists
        $invoice_model_factory = new Invoice\InvoiceModelFactory();
        $model = $invoice_model_factory->GetById($invoice_id);
        $amendments_only = $req->amendments_only === null ? 0 : 1;

        $pdf = PDF::loadView('invoices.invoice_table', compact('model', 'amendments_only'));
        return $pdf->stream($model->parent->name . '.' . $model->invoice->date . '.pdf');
    }

    public function printMass($invoiceIds) {
        $storagepath = storage_path() . '/app/public/';
        $foldername = 'invoices.' . time();
        mkdir($storagepath . $foldername);
        $path = $storagepath . $foldername . '/';
        $files = array();

        $zip = new ZipArchive();
        $zipfile = $storagepath . $foldername . '.zip';
        $zip->open($zipfile, ZipArchive::CREATE);

        $toBeUnlinked =  array();

        foreach(explode(',', $invoiceIds) as $invoiceId) {
            $invoiceModelFactory = new Invoice\InvoiceModelFactory();
            $model = $invoiceModelFactory->GetById($invoiceId);
            $filename = $model->parent->name . '-' . $model->invoice->invoice_id . '.pdf';
            $is_pdf = 1;
            $pdf = PDF::loadView('invoices.invoice_table', compact('model', 'is_pdf'));
            $pdf->save($path . $filename, $filename);
            $zip->addFile($path . $filename, $filename);
            $toBeUnlinked[$invoiceId] = $path . $filename;
        }

        $zip->close();

        foreach($toBeUnlinked as $file)
            unlink($file);
        rmdir($storagepath . $foldername);

        // return $foldername . '.zip';
        return \Response::download($zipfile);
    }
}
