<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Jobs\ReceiveStripeWebhook;


class WebhookController extends Controller {
    public function ReceivePaymentIntentUpdate(Request $req) {
        try {
            $event = \Stripe\Webhook::constructEvent($req->getContent(), $req->header('Stripe-Signature'), config('services.stripe.webhook_secret'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook signature verification failed'], 403);
        }
        // If the event is valid, dispatch it to a job for further processing
        ReceiveStripeWebhook::dispatch($event);

        //Acknowledge that the request was received and successfully queued
        return response()->json(['success' => 'Webhook received and queued'], 200);
    }
}

?>
