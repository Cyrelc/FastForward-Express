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

    public function createAmendment(Request $req) {
        DB::beginTransaction();
        $amendmentValidation = new \App\Http\Validation\AmendmentValidationRules();
        $validationRules = $amendmentValidation->GetValidationRules($req);

        $this->validate($req, $validationRules['rules'], $validationRules['messages']);

        $amendmentCollector = new Collectors\AmendmentCollector();
        $amendment = $amendmentCollector->collect($req);

        $invoiceRepo = new Repos\InvoiceRepo();

        //check whether invoice is within date window for change
        $invoice = $invoiceRepo->GetById($req->invoice_id);
        if($req->user()->cannot('update', $invoice))
            abort(403);

        $billEndDate = new \DateTime($invoice->bill_end_date);
        $currentDate = new \DateTime('now');
        $diff = $currentDate->diff($billEndDate, true);

        if($diff->days < (int)config('ffe_config.days_invoice_editable'))
            $invoiceRepo->InsertAmendment($amendment);
        else
            throw new \Exception('Invoices older than ' . config('ffe_config.days_invoice_editable') . ' days old can no longer be edited.');

        DB::commit();
    }

    public function delete(Request $req, $invoiceId) {
        DB::beginTransaction();

        $invoiceRepo = new Repos\InvoiceRepo();
        if($req->user()->cannot('delete', $invoiceRepo->GetById($invoiceId)))
            abort(403);

        $invoiceRepo->delete($id);
        DB::commit();

        return response()->json([
            'success' => true
        ]);
    }

    public function deleteAmendment(Request $req, $amendmentId) {
        DB::beginTransaction();
        $invoiceRepo = new Repos\InvoiceRepo();

        $amendment = $invoiceRepo->GetAmendmentById($amendmentId);
        $invoice = $invoiceRepo->GetById($amendment->invoice_id);

        if($req->user()->cannot('update', $invoice))
            abort(403);

        $billEndDate = new \DateTime($invoice->bill_end_date);
        $currentDate = new \DateTime('now');
        $diff = $currentDate->diff($billEndDate, true);
        if($diff->days < (int)config('ffe_config.days_invoice_editable'))
            $invoiceRepo->DeleteAmendment($amendmentId);
        else
            throw new \Exception('Invoices older than ' . config('ffe_config.days_invoice_editable') . ' days old can no longer be edited.');

        DB::commit();
    }

    public function download(Request $req, $filename) {
        $path = storage_path() . '/app/public/';
        return response()->download($path . $filename)->deleteFileAfterSend(true);
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
        $invoice_repo = new Repos\InvoiceRepo();

        $invoices = $invoiceRepo->GetOutstandingByAccountId($req->input('account_id'));
        foreach($invoices as $invoice)
            if($req->user()->cannot('view', $invoice))
                abort(403);

        return json_encode($invoices);
    }

    public function print(Request $req, $invoiceId) {
        //TODO check if invoice $id exists
        $invoiceModelFactory = new Invoice\InvoiceModelFactory();
        $model = $invoiceModelFactory->GetById($req, $invoiceId);
        $amendmentsOnly = $req->amendments_only === null ? 0 : 1;
        if($req->user()->cannot('view', $model->invoice))
            abort(403);

        $pdf = PDF::loadView('invoices.invoice_table', compact('model', 'amendmentsOnly'));
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

            if($req->user()->cannot('view', $model->invoice))
                abort(403);

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

        return \Response::download($zipfile);
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

        $invoiceRepo->create($req->accounts, $startDate, $endDate);

        DB::commit();
    }
}
