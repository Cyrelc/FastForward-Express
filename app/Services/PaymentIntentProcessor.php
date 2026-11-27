<?php

namespace App\Services;

use App\Http\Repos;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;

/**
 * Brings our payment records in line with a Stripe PaymentIntent.
 *
 * A webhook only tells us "this PaymentIntent changed". sync() fetches its current state from Stripe and applies
 * that, so duplicate, late and out-of-order events all converge on the same result, and running it again (the
 * receipts tool, stripe:reconcile) is harmless. The one effect that must happen exactly once - taking the payment
 * off the invoice balance - is tied to the payment row's move into 'succeeded', made while holding a row lock.
 */
class PaymentIntentProcessor {
    public function sync(string $paymentIntentId) {
        $paymentIntent = app(StripeClient::class)->paymentIntents->retrieve($paymentIntentId, ['expand' => ['latest_charge']]);

        $this->apply($paymentIntent);
    }

    /**
     * Applies a PaymentIntent's state. Pass state fetched from Stripe, not an event payload, which may be stale.
     */
    public function apply($paymentIntent) {
        $paymentRepo = new Repos\PaymentRepo();

        // Read before the transaction: it may call the Stripe API, and a failure there should just retry the job
        $charge = $this->getCharge($paymentIntent);
        $card = $charge->payment_method_details->card ?? null;
        $paymentType = ($card ? $paymentRepo->GetPaymentTypeByName($card->brand) : null)
            ?? $paymentRepo->GetPaymentTypeByName('Stripe (Pending)');

        DB::transaction(function() use ($paymentIntent, $charge, $card, $paymentType) {
            // Refund rows copy the payment's stripe_payment_intent_id, so only the payment itself is matched
            $payments = Payment::where('stripe_payment_intent_id', $paymentIntent->id)
                ->where('stripe_object_type', 'payment_intent')
                ->lockForUpdate()
                ->get();

            // Card-on-file payments save their row after Stripe has charged, so the webhook can arrive first.
            // Throwing makes the job retry with a backoff, instead of quietly dropping the update.
            if($payments->isEmpty())
                throw new \RuntimeException('No payment record for Stripe PaymentIntent ' . $paymentIntent->id);

            foreach($payments as $payment)
                $this->applyToPayment($payment, $paymentIntent, $charge, $card, $paymentType);
        });
    }

    private function applyToPayment(Payment $payment, $paymentIntent, $charge, $card, $paymentType) {
        // Card details exist only once there is a charge. Before that, keep what the row already has
        // (card-on-file rows are saved with the card's payment type).
        $details = [];
        if($card && $paymentType) {
            $details['payment_type_id'] = $paymentType->payment_type_id;
            $details['reference_value'] = '**** **** **** ' . $card->last4;
        }
        if($charge->receipt_url ?? null)
            $details['receipt_url'] = $charge->receipt_url;

        if($payment->stripe_status === 'succeeded') {
            // Terminal: the money has been applied. Stripe never moves a PaymentIntent out of 'succeeded'.
            if($paymentIntent->status !== 'succeeded')
                activity('stripe')->performedOn($payment)->event('error')
                    ->withProperties(['stripe_payment_intent_id' => $paymentIntent->id, 'stripe_status' => $paymentIntent->status])
                    ->log('[PaymentIntentProcessor] succeeded payment reported as ' . $paymentIntent->status . '; ignored');
            else if($details)
                $payment->update($details);

            return;
        }

        $update = $details + [
            'stripe_status' => $paymentIntent->status,
            // Also recorded when a payment goes back to requires_payment_method after a decline
            'error' => $paymentIntent->last_payment_error->message ?? null,
        ];

        if($paymentIntent->status !== 'succeeded') {
            $payment->update($update);
            return;
        }

        $paymentAmount = bcdiv($paymentIntent->amount_received, 100, 2);
        $payment->update($update + ['amount' => $paymentAmount]);

        activity('stripe')
            ->performedOn($payment)
            ->withProperties([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentAmount,
                'receipt_url' => $details['receipt_url'] ?? null,
            ])->log('[PaymentIntentProcessor] succeeded');

        if(Invoice::find($payment->invoice_id)->balance_owing == 0)
            report(new \Exception('Attempting to double pay invoice #' . $payment->invoice_id));
        else
            (new Repos\InvoiceRepo())->AdjustBalanceOwing($payment->invoice_id, -$paymentAmount);
    }

    /**
     * The charge behind a PaymentIntent, from either API shape:
     *  - before API 2022-11-15 it is embedded in the `charges` list
     *  - since then only `latest_charge` is sent: a charge ID, or the object when the request expanded it
     */
    public function getCharge($paymentIntent) {
        if(isset($paymentIntent->charges))
            return $paymentIntent->charges->data[0] ?? null;

        $latestCharge = $paymentIntent->latest_charge ?? null;
        if(is_string($latestCharge))
            return app(StripeClient::class)->charges->retrieve($latestCharge);

        return $latestCharge;
    }
}
