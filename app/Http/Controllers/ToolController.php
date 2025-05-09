<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentIntentProcessor;
use Illuminate\Http\Request;
use \Stripe;

class ToolController extends Controller {
    public function getStripeReceipts(Request $req) {
        $stripe = new Stripe\StripeClient(config('services.stripe.secret'));
        $payments = Payment::whereNotNull('stripe_payment_intent_id')
        ->where('stripe_object_type', 'payment_intent')
        ->whereNull('receipt_url')
        ->whereIn('stripe_status', ['succeeded', 'pending'])
        ->get();

        $successCount = 0;

        foreach($payments as $payment) {
            try {
                $paymentIntent = $stripe->paymentIntents->retrieve($payment->stripe_payment_intent_id);

                $fakeEvent = (object)[
                    'data' => (object)[
                        'object' => $paymentIntent
                    ]
                ];

                $processor = app(PaymentIntentProcessor::class);
                try {
                    $processor->ProcessPaymentIntent($fakeEvent);
                } catch (\Throwable $e) {
                    \Log::error("Processing refresh for {$payment->stripe_payment_intent_id} failed: {$e->getMessage()}");
                    continue;
                }
                $successCount++;
            } catch (\Exception $e) {
                \Log::error("Error processing payment intent {$payment->stripe_payment_intent_id}: {$e->getMessage()}", [
                    'exception' => $e
                ]);
                continue;
            }
        }

        return response()->json([
            'success' => true,
            'count' => $successCount,
        ]);
    }

}
