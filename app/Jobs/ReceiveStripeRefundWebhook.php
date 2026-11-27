<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\StripeRefundProcessor;

/**
 * Syncs one Refund from Stripe. See ReceiveStripeWebhook for why it carries only IDs and is unique until processing.
 */
class ReceiveStripeRefundWebhook implements ShouldQueue, ShouldBeUniqueUntilProcessing {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $uniqueFor = 600;

    public function __construct(public string $refundId, public ?string $eventId = null) {
        activity('jobs')->log('Creating pending job for Stripe refund ' . $refundId);
    }

    public function handle(StripeRefundProcessor $processor): void {
        activity('jobs')->log('Handling pending job for Stripe refund: ' . $this->refundId);
        $processor->sync($this->refundId);
    }

    /**
     * Stripe can deliver the refund webhook before revertPayment has saved our refund row. Without a backoff
     * the five tries are spent immediately, all failing to find that row.
     */
    public function backoff() {
        return [5, 15, 30, 60];
    }

    public function uniqueId() {
        return $this->refundId;
    }
}
