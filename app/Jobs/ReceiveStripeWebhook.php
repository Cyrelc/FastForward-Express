<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\PaymentIntentProcessor;

/**
 * Syncs one PaymentIntent from Stripe.
 *
 * Carries only IDs: the PaymentIntent's state is fetched when the job runs, so a burst of events needs only one
 * job. The unique lock is released as soon as the job starts (UntilProcessing), so an event that arrives while it
 * runs queues a fresh job and the final state is never missed. Holding the lock until the job finished (plain
 * ShouldBeUnique) silently dropped those events.
 */
class ReceiveStripeWebhook implements ShouldQueue, ShouldBeUniqueUntilProcessing {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $uniqueFor = 600;

    public function __construct(public string $paymentIntentId, public ?string $eventId = null) {
        activity('jobs')->log('Creating pending job for Stripe transaction ' . $paymentIntentId);
    }

    public function handle(PaymentIntentProcessor $processor) {
        activity('jobs')->log('Handling pending job for Stripe transaction: ' . $this->paymentIntentId);
        $processor->sync($this->paymentIntentId);
    }

    /**
     * Also covers card-on-file payments, whose webhook can arrive before our payment row is saved
     */
    public function backoff() {
        return [5, 15, 30, 60];
    }

    public function uniqueId() {
        return $this->paymentIntentId;
    }
}
