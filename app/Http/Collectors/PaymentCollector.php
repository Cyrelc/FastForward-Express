<?php

namespace App\Http\Collectors;

use App\Http\Repos;

class PaymentCollector {

    public function CollectAccountCredit($req) {
        $paymentRepo = new Repos\PaymentRepo();

        return [
            'account_id' => $req->account_id,
            'date' => date('Y-m-d'),
            'amount' => $req->credit_amount,
            'payment_type_id' => $paymentRepo->GetPaymentTypeByName('Account')->payment_type_id,
            'reference_value' => 'Price adjustment on bill #' . $req->bill_id,
            'comment' => $req->description
        ];
    }

    public function CollectAccountPayment($req, $account_adjustment) {
        return [
            'account_id' => $req->input('account-id'),
            'date' => date('Y-m-d'),
            'amount' => $account_adjustment,
            'payment_type_id' => $req->payment_type_id,
            'reference_value' => $req->reference_value,
            'comment' => $req->comment
        ];
    }

    public function CollectInvoicePayment($req, $outstandingInvoice) {
        return [
            'account_id' => $req->account_id,
            'invoice_id' => $outstandingInvoice['invoice_id'],
            'date' => date('Y-m-d'),
            'amount' => $outstandingInvoice['payment_amount'],
            'payment_type_id' => $req->payment_type_id,
            'reference_value' => $req->reference_value,
            'comment' => $req->comment
        ];
    }

    public function CollectBillPayment($req) {
        return [
            'account_id' => null,
            'invoice_id' => null,
            'date' => date('Y-m-d'),
            'amount' => isset($req->amount) ? $req->amount : 0 + isset($req->interliner_cost_to_customer) ? $req->interliner_cost_to_customer : 0,
            'payment_type_id' => $req->payment_type['payment_type_id'],
            'reference_value' => $req->charge_reference_value,
            'comment' => null
        ];
    }
}
?>
