<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentIntentProcessor;
use App\Services\StripeRefundProcessor;
use Illuminate\Console\Command;

/**
 * Backstop for missed webhooks (endpoint down, worker down, events dropped): re-syncs every Stripe payment and
 * refund still in flight. Syncing is idempotent and reads current state from Stripe, so overlapping with a
 * webhook for the same payment is harmless.
 */
class ReconcileStripePayments extends Command {
    protected $signature = 'stripe:reconcile {--days=30 : How many days back to look for in-flight payments}';

    protected $description = 'Re-sync in-flight Stripe payments and refunds, in case a webhook was missed';

    // States Stripe resolves without the customer. Abandoned checkouts (requires_payment_method,
    // requires_action) would never resolve and would cost an API call every run.
    private const IN_FLIGHT_PAYMENT_STATUSES = ['pending', 'processing'];
    private const IN_FLIGHT_REFUND_STATUSES = ['pending', 'requires_action'];

    public function handle(PaymentIntentProcessor $payments, StripeRefundProcessor $refunds) {
        $paymentIntentIds = Payment::where('stripe_object_type', 'payment_intent')
            ->whereIn('stripe_status', self::IN_FLIGHT_PAYMENT_STATUSES)
            ->where('date', '>=', now()->subDays((int)$this->option('days'))->toDateString())
            ->pluck('stripe_payment_intent_id')
            ->unique();

        // Not limited by date: a refund row carries the original payment's date
        $refundIds = Payment::where('stripe_object_type', 'refund')
            ->whereIn('stripe_status', self::IN_FLIGHT_REFUND_STATUSES)
            ->pluck('stripe_refund_id')
            ->unique();

        $failures = 0;
        foreach($paymentIntentIds as $id)
            $failures += $this->attempt(fn() => $payments->sync($id), $id);
        foreach($refundIds as $id)
            $failures += $this->attempt(fn() => $refunds->sync($id), $id);

        $this->info("Synced {$paymentIntentIds->count()} payments and {$refundIds->count()} refunds; {$failures} failed");

        return $failures ? self::FAILURE : self::SUCCESS;
    }

    private function attempt(callable $sync, $id) {
        try {
            $sync();
            return 0;
        } catch (\Throwable $e) {
            report($e);
            $this->error("{$id}: {$e->getMessage()}");
            return 1;
        }
    }
}
