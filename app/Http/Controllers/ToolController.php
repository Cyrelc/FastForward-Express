<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentIntentProcessor;
use Illuminate\Http\Request;

class ToolController extends Controller {
    public function getStripeReceipts(Request $req) {
        // Re-syncs every Stripe payment still missing its receipt, one Stripe API call per payment
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $paymentIntentIds = Payment::whereNotNull('stripe_payment_intent_id')
            ->where('stripe_object_type', 'payment_intent')
            ->whereNull('receipt_url')
            ->whereIn('stripe_status', ['succeeded', 'pending'])
            ->pluck('stripe_payment_intent_id')
            ->unique();

        $processor = app(PaymentIntentProcessor::class);
        $successCount = 0;

        foreach($paymentIntentIds as $paymentIntentId) {
            try {
                $processor->sync($paymentIntentId);
                $successCount++;
            } catch (\Throwable $e) {
                \Log::error("Re-syncing Stripe payment intent {$paymentIntentId} failed: {$e->getMessage()}", ['exception' => $e]);
            }
        }

        return response()->json([
            'success' => true,
            'count' => $successCount,
        ]);
    }
}
