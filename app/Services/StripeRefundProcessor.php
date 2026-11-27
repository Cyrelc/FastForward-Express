<?php

namespace App\Services;

use App\Http\Repos\InvoiceRepo;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;

/**
 * Brings our refund records in line with a Stripe Refund. Same approach as PaymentIntentProcessor: fetch the
 * current state, and tie the invoice adjustment to the row's transitions, made under a row lock.
 */
class StripeRefundProcessor {
    public function sync(string $refundId) {
        $this->apply(app(StripeClient::class)->refunds->retrieve($refundId));
    }

    /**
     * Applies a Refund's state. Pass state fetched from Stripe, not an event payload, which may be stale.
     */
    public function apply($refund) {
        DB::transaction(function() use ($refund) {
            $payment = Payment::where('stripe_refund_id', $refund->id)->lockForUpdate()->first();

            // revertPayment saves our row only after Stripe has created the refund, so the webhook can arrive
            // first. Throwing makes the job retry with a backoff.
            if(!$payment)
                throw new \RuntimeException('No refund record for Stripe refund ' . $refund->id);

            $wasSucceeded = $payment->stripe_status === 'succeeded';
            $isSucceeded = $refund->status === 'succeeded';
            $refundAmount = bcdiv($refund->amount, 100, 2);

            $payment->update([
                'error' => $refund->failure_message ?? null,
                'stripe_status' => $refund->status,
            ]);

            if(!$payment->invoice_id || $wasSucceeded === $isSucceeded)
                return;

            // A refund puts the money back on the invoice. Stripe can still fail a refund after it succeeded
            // (e.g. the card was closed), which takes it off again.
            $adjustment = $isSucceeded ? $refundAmount : -$refundAmount;
            (new InvoiceRepo())->AdjustBalanceOwing($payment->invoice_id, $adjustment);

            activity('stripe')
                ->performedOn($payment)
                ->withProperties([
                    'refund_id' => $refund->id,
                    'stripe_payment_intent_id' => $refund->payment_intent,
                    'stripe_status' => $refund->status,
                    'balance_owing_adjustment' => $adjustment,
                ])->log($isSucceeded ? '[StripeRefundProcessor] succeeded' : '[StripeRefundProcessor] reversed after ' . $refund->status);
        });
    }
}
