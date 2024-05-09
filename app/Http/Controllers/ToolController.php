<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Stripe;

class ToolController extends Controller {
    public function getStripeReceipts(Request $req) {
        $stripe = new Stripe\StripeClient(config('services.stripe.secret'));
        $payments = \App\Models\Payment::whereNotNull('payment_intent_id')
            ->whereNull('receipt_url')
            // ->where('payment_intent_status', 'like', 'succeeded')
            ->get();

        $successCount = 0;

        foreach($payments as $payment) {
            try {
                $paymentIntent = $stripe->paymentIntents->retrieve($payment->payment_intent_id);

                if($paymentIntent->charges->data[0]->receipt_url)
                    $payment->update(['receipt_url' => $paymentIntent->charges->data[0]->receipt_url]);
                $successCount++;
            } catch (\Exception $e) {
                \Log::error("Error processing payment intent {$payment->payment_intent_id}: {$e->getMessage()}", [
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
