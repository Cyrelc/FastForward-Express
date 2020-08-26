<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Collectors;
use App\Http\Repos;
use App\Http\Validation;

class AmendmentController extends Controller {
    public function delete($amendmentId) {
        DB::beginTransaction();
        try {
            $amendmentRepo = new Repos\AmendmentRepo();
            $invoiceRepo = new Repos\InvoiceRepo();
            
            $amendment = $amendmentRepo->GetById($amendmentId);
            $invoice = $invoiceRepo->GetById($amendment->invoice_id);

            $billEndDate = new \DateTime($invoice->bill_end_date);
            $currentDate = new \DateTime('now');
            $diff = $currentDate->diff($billEndDate, true);
            if($diff->days < (int)config('ffe_config.days_invoice_editable')) {
                $invoiceRepo->AdjustBillSubtotal($amendment->invoice_id, -$amendment->amount);
                $amendmentRepo->Delete($amendmentId);
            }
            else
                throw new Exception('Invoices older than ' + config('ffe_config.days_invoice_editable' + ' days old can no longer be edited.'));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

    }

    public function store(Request $req) {
        DB::beginTransaction();
        try {
            $amendmentValidation = new \App\Http\Validation\AmendmentValidationRules();
            $validationRules = $amendmentValidation->GetValidationRules($req);

            $this->validate($req, $validationRules['rules'], $validationRules['messages']);

            $amendmentCollector = new Collectors\AmendmentCollector();
            $amendment = $amendmentCollector->collect($req);

            $amendmentRepo = new Repos\AmendmentRepo();
            $invoiceRepo = new Repos\InvoiceRepo();

            if($req->amendment_id)
                $amendmentRepo->Update($amendment);
            else
                $amendmentRepo->Insert($amendment);

            $invoiceRepo->AdjustBillSubtotal($amendment['invoice_id'], $amendment['amount']);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
