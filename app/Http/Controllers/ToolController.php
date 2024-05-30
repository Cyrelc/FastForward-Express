<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Stripe;

class ToolController extends Controller {
    public function getStripeReceipts(Request $req) {
        $stripe = new Stripe\StripeClient(config('services.stripe.secret'));
        $payments = \App\Models\Payment::whereNotNull('stripe_id')
            ->where('stripe_object_type', 'payment_intent')
            ->whereNull('receipt_url')
            ->where('stripe_status', 'like', 'succeeded')
            ->get();

        $successCount = 0;

        foreach($payments as $payment) {
            try {
                $paymentIntent = $stripe->paymentIntents->retrieve($payment->stripe_id);

                if($paymentIntent->charges->data[0]->receipt_url)
                    $payment->update(['receipt_url' => $paymentIntent->charges->data[0]->receipt_url]);
                $successCount++;
            } catch (\Exception $e) {
                \Log::error("Error processing payment intent {$payment->stripe_id}: {$e->getMessage()}", [
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
