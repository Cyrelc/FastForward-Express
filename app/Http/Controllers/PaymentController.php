<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Collectors;
use App\Http\Models\Payment;
use \App\Http\Validation\Utils;

class PaymentController extends Controller {

    public function GetPaymentsTableByAccount(Request $req) {
        $paymentsRepo = new Repos\PaymentRepo();
        return json_encode($paymentsRepo->listPaymentsByAccount($req->input('account_id')));
    }

    public function ProcessAccountPayment(Request $req) {
        DB::beginTransaction();
        try {
            $paymentRepo = new Repos\PaymentRepo();
            $invoiceRepo = new Repos\InvoiceRepo();
            $accountRepo = new Repos\AccountRepo();

            $paymentCollector = new Collectors\PaymentCollector();

            $paymentValidation = new \App\Http\Validation\PaymentValidationRules();

            $account_id = $req->input('account-id');
            $accountPaymentTypeId = $paymentRepo->GetPaymentTypeByName('Account')->payment_type_id;
            $outstanding_invoices = $invoiceRepo->GetOutstandingByAccountId($account_id);

            $temp = $paymentValidation->GetPaymentOnAccountRules($req, $outstanding_invoices);

            $this->validate($req, $temp['rules'], $temp['messages']);

            if($req->input('payment_type_id') == $accountPaymentTypeId)
                $account_adjustment = -(float)$req->input('payment_amount');
            else
                $account_adjustment = (float)$req->input('payment_amount');

            foreach($outstanding_invoices as $invoice) {
                $payment_amount = $req->input($invoice->invoice_id . '_payment_amount');
                if($payment_amount > 0) {
                    $paymentRepo->insert($paymentCollector->CollectInvoicePayment($req, $account_id, $invoice->invoice_id));
                    $invoiceRepo->AdjustBalanceOwing($invoice->invoice_id, -$payment_amount);
                    if($req->payment_type_id != $accountPaymentTypeId)
                        $account_adjustment -= $req->input($invoice->invoice_id . '_payment_amount');
                }
            }

            if($account_adjustment != 0)
                $accountRepo->AdjustBalance($account_id, $account_adjustment);

            DB::commit();
            return response()->json(['success' => true]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>
