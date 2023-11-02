<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;


class WebhookController extends Controller {
    public function HandlePaymentIntentUpdate(Request $req) {
        try {
            $event = \Stripe\Webhook::constructEvent($req->getContent(), $req->header('Stripe-Signature'), env('STRIPE_WEBHOOK_SECRET'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook signature verification failed'], 403);
        }

        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        DB::beginTransaction();

        $paymentIntent = $event->data->object;
        try {
            $card = $paymentIntent->charges->data[0]->payment_method_details->card;
        } catch (\Throwable $e) {
            $card = null;
        }

        $stripePendingPaymentType = $paymentRepo->GetPaymentTypeByName("Stripe (Pending)");
        $newPaymentType = $card ? $paymentRepo->GetPaymentTypeByName($card->brand) : $stripePendingPaymentType;
        $payments = $paymentRepo->GetPaymentsByPaymentIntentId($paymentIntent->id);

        $paymentRepo->UpdatePaymentIntentStatus($paymentIntent->id, $event->type);

        foreach($payments as $payment) {
            if($payment->payment_type_id == $stripePendingPaymentType->payment_type_id && $newPaymentType) {
                $paymentRepo->Update($payment->payment_id, [
                    'amount' => $payment->amount,
                    'payment_type_id' => $newPaymentType->payment_type_id,
                    'reference_value' => $card ? $card->last4 : null,
                ]);
            }
            if($event->type == 'payment_intent.succeeded')
                $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, -$payment->amount);
        }

        DB::commit();

        return response()->json(['success' => true]);
    }
}

?>
