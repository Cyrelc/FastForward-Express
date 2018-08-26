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
}
?>
