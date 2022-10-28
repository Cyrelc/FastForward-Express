<?php

namespace App\Http\Collectors;

use App\Http\Repos;

class PaymentCollector {
    public function CollectAccountCredit($req) {
        $paymentRepo = new Repos\PaymentRepo();

        return [
            'account_id' => $req->account_id,
            'amount' => $req->credit_amount,
            'comment' => $req->description,
            'date' => date('Y-m-d'),
            'payment_type_id' => $paymentRepo->GetPaymentTypeByName('Account')->payment_type_id,
            'reference_value' => 'Price adjustment on bill #' . $req->bill_id,
        ];
    }

    public function CollectAccountPayment($req, $account_adjustment, $comment = null) {
        return [
            'account_id' => $req->account_id,
            'amount' => $account_adjustment,
            'comment' => $comment ? $comment : $req->comment,
            'date' => date('Y-m-d'),
            'payment_type_id' => $req->payment_type_id,
            'reference_value' => $req->reference_value,
        ];
    }

    public function CollectInvoicePayment($req, $outstandingInvoice, $paymentIntent) {
        $isStripePaymentMethod = filter_var($req->payment_method_on_file, FILTER_VALIDATE_BOOLEAN);
        return [
            'account_id' => $req->account_id,
            'amount' => $outstandingInvoice['payment_amount'],
            'comment' => $req->comment,
            'date' => date('Y-m-d'),
            'invoice_id' => $outstandingInvoice['invoice_id'],
            'payment_intent_id' => $isStripePaymentMethod ? $paymentIntent->id : null,
            'payment_type_id' => $req->payment_type_id,
            'reference_value' => $req->reference_value
        ];
    }

    // public function CollectBillPayment($req) {
    //     $amount = 0;
    //     if(isset($req->amount)) {
    //         $amount = $req->amount;
    //     } else if(isset($req->interliner_cost_to_customer)) {
    //         $amount = $req->interliner_cost_to_customer;
    //     }

    //     return [
    //         'account_id' => null,
    //         'amount' => $amount,
    //         'comment' => null,
    //         'date' => date('Y-m-d'),
    //         'invoice_id' => null,
    //         'payment_type_id' => $req->payment_type['payment_type_id'],
    //         'reference_value' => $req->charge_reference_value,
    //     ];
    // }
}
?>
