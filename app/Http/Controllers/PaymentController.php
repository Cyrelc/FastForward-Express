<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Collectors;
use App\Http\Models\Payment;
use \App\Http\Validation\Utils;

class PaymentController extends Controller {
    public function GetModelByAccountId(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);
        if($req->user()->cannot('viewPayments', $account))
            abort(403);

        $paymentModelFactory = new Models\Payment\PaymentModelFactory();
        $paymentModel = $paymentModelFactory->GetModelByAccountId($accountId);

        return json_encode($paymentModel);
    }

    public function ProcessAccountPayment(Request $req) {
        if($req->user()->cannot('create', Payment::class))
            abort(403);

        DB::beginTransaction();

        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $paymentCollector = new Collectors\PaymentCollector();

        $paymentValidation = new \App\Http\Validation\PaymentValidationRules();

        $accountPaymentTypeId = $paymentRepo->GetPaymentTypeByName('Account')->payment_type_id;

        $temp = $paymentValidation->GetPaymentOnAccountRules($req);

        $this->validate($req, $temp['rules'], $temp['messages']);

        if($req->input('payment_type_id') == $accountPaymentTypeId)
            $accountAdjustment = -(float)$req->input('payment_amount');
        else
            $accountAdjustment = (float)$req->input('payment_amount');

        foreach($req->outstanding_invoices as $outstandingInvoice) {
            if($outstandingInvoice['payment_amount'] && $outstandingInvoice['payment_amount'] > 0 && $invoiceRepo->GetById($outstandingInvoice['invoice_id'])->balance_owing > 0) {
                $paymentRepo->insert($paymentCollector->CollectInvoicePayment($req, $outstandingInvoice));
                $invoiceRepo->AdjustBalanceOwing($outstandingInvoice['invoice_id'], -$outstandingInvoice['payment_amount']);
                if($req->payment_type_id != $accountPaymentTypeId)
                    $accountAdjustment -= $outstandingInvoice['payment_amount'];
            }
        }

        if($accountAdjustment != 0) {
            $accountRepo->AdjustBalance($req->account_id, $accountAdjustment);
            $paymentRepo->insert($paymentCollector->CollectAccountPayment($req, $accountAdjustment));
        }

        DB::commit();
        return response()->json(['success' => true]);
    }
}
?>
