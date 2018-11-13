<?php

namespace App\Http\Collectors;

class PaymentCollector {

    public function CollectAccountPayment($req, $account_adjustment) {
        return [
            'account_id' => $req->input('account-id'),
            'date' => date('Y-m-d'),
            'amount' => $account_adjustment,
            'payment_type' => $req->select_payment,
            'reference_value' => $req->reference_value,
            'comment' => $req->comment
        ];
    }

    public function CollectInvoicePayment($req, $account_id, $invoice_id) {
        return [
            'account_id' => $account_id,
            'invoice_id' => $invoice_id,
            'date' => date('Y-m-d'),
            'amount' => $req->input($invoice_id . '_payment_amount'),
            'payment_type' => $req->select_payment,
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
            'payment_type' => $req->prepaid_type,
            'reference_value' => $req->prepaid_reference_value,
            'comment' => null
        ];
    }
}
?>
