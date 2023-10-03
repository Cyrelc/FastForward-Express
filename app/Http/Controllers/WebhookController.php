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

        $paymentIntent = $event->data->object;
        switch($event->type) {
            case 'payment_intent.succeeded':
                $payment = $paymentRepo->GetPaymentByPaymentIntentId($paymentIntent->id);
                if(!$payment || $payment->account_id)
                    break;
                $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, -$paymentIntent->amount / 100);
            default:
                $paymentRepo->UpdatePaymentIntentStatus($paymentIntent->id, $event->type);
        }

        return response()->json(['success' => true]);
    }
}

?>
