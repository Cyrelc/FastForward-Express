<?php

namespace App\Http\Collectors;

use App\Http\Repos;

class PaymentCollector {
    // Similar to CollectAccountPayment, but automatically applies the account payment type, and a comment that this is a price adjustment on a bill
    public function CollectAccountCredit($req) {
        $paymentRepo = new Repos\PaymentRepo();

        return [
            'account_id' => $req->account_id,
            'amount' => $req->credit_amount,
            'comment' => $req->description,
            'date' => date('Y-m-d'),
            'payment_type_id' => $paymentRepo->GetPaymentTypeByName('Account')->payment_type_id,
            'reference_value' => 'Price adjustment on ' . $req->track_against_type . ' #' . $req->track_against_id,
        ];
    }

    // The default collection used for account Invoice payments - with or without a paymentIntent (Stripe transaction)
    public function CollectAccountInvoicePayment($req, $outstandingInvoice, $paymentIntent = null) {
        $isStripePaymentMethod = filter_var($req->payment_method_on_file, FILTER_VALIDATE_BOOLEAN);
        return [
            'account_id' => $req->account_id,
            'amount' => $outstandingInvoice['payment_amount'],
            'comment' => $req->comment ?? null,
            'date' => date('Y-m-d'),
            'invoice_id' => $outstandingInvoice['invoice_id'],
            'payment_intent_id' => ($isStripePaymentMethod && $paymentIntent) ? $paymentIntent->id : null,
            'payment_type_id' => $req->payment_type_id,
            'reference_value' => $req->reference_value,
            'payment_intent_status' => $paymentIntent ? 'payment_intent.pending' : null
        ];
    }

    // Similar to CollectAccountCredit, but allows for setting the amount and comment from outside of the request
    public function CollectAccountPayment($req, $accountAdjustment, $comment = null) {
        return [
            'account_id' => $req->account_id,
            'amount' => $accountAdjustment,
            'comment' => $comment ?? $req->comment ?? null,
            'date' => date('Y-m-d'),
            'payment_type_id' => $req->payment_type_id,
            'reference_value' => $req->reference_value,
        ];
    }

    // Collection used for direct-to-invoice payments where there is no account involved.
    // Used exclusively for prepaid payment types, such as Visa or Mastercard, as these require a paymentIntent
    public function CollectInvoicePayment($req, $outstandingInvoice, $paymentIntent) {
        $paymentRepo = new Repos\PaymentRepo();
        $stripePaymentType = $paymentRepo->GetPaymentTypeByName("Stripe (Pending)");
        return [
            'account_id' => null,
            'amount' => $req->amount,
            'comment' => null,
            'date' => gmdate("Y-m-d\TH:i:s\Z", $paymentIntent->created),
            'invoice_id' => $req->invoice_id,
            'payment_intent_id' => $paymentIntent->id,
            'payment_type_id' => $stripePaymentType->payment_type_id,
            'reference_value' => null
        ];
    }
}
?>
