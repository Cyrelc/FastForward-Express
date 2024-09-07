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

    public function CollectPaymentFromAccount($req, $invoice) {
        return [
            'account_id' => $invoice->account_id ?? null,
            'amount' => $req->amount,
            'comment' => 'Payment from account credit',
            'date' => date('Y-m-d'),
            'invoice_id' => $invoice->invoice_id,
            'payment_type_id' => $req->payment_method['payment_type_id']
        ];
    }

    public function CollectPrepaid($req, $invoice) {
        return [
            'account_id' => $invoice->account_id ?? null,
            'amount' => $req->amount,
            'comment' => $req->comment ?? 'Payment collected manually',
            'date' => date('Y-m-d'),
            'invoice_id' => $invoice->invoice_id,
            'payment_type_id' => $req->payment_method['payment_type_id'],
            'reference_value' => $req->reference_value ?? null
        ];
    }

    // The default collection used for account Invoice payments - with or without a paymentIntent (Stripe transaction)
    public function CollectCardOnFile($req, $invoice, $paymentIntent) {
        return [
            'account_id' => $invoice->account->account_id,
            'amount' => $req->amount,
            'comment' => $req->comment == "" ? null : $req->comment,
            'date' => date('Y-m-d'),
            'invoice_id' => $invoice->invoice_id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'stripe_object_type' => 'payment_intent',
            'stripe_status' => 'pending',
            'payment_type_id' => $req->payment_method['payment_type_id'],
            'reference_value' => $req->payment_method['name'],
        ];
    }

    // Collection used for direct-to-invoice payments where there is no account involved.
    // Used exclusively for prepaid payment types, such as Visa or Mastercard, as these require a paymentIntent
    public function CollectStripePaymentIntent($req, $outstandingInvoice, $paymentIntent) {
        $paymentRepo = new Repos\PaymentRepo();
        $stripePaymentType = $paymentRepo->GetPaymentTypeByName("Stripe (Pending)");
        return [
            'account_id' => $outstandingInvoice->account_id ?? null,
            'amount' => $req->amount,
            'comment' => null,
            'date' => gmdate("Y-m-d\TH:i:s\Z", $paymentIntent->created),
            'invoice_id' => $req->invoice_id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'stripe_status' => $paymentIntent->status,
            'payment_type_id' => $stripePaymentType->payment_type_id,
            'reference_value' => null
        ];
    }
}
?>
