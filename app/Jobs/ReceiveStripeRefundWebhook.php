<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\StripeRefundProcessor;

class ReceiveStripeRefundWebhook implements ShouldQueue, ShouldBeUnique {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $uniqueFor = 600;

    protected $event;

    /**
     * Create a new job instance.
     */
    public function __construct($event) {
        activity('jobs')->log('Creating pending job for Stripe refund ' . $event->data->object->id);
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(StripeRefundProcessor $processor): void {
        activity('jobs')->log('Handling pending job for Stripe refund: ' . $this->event->data->object->id);
        $processor->processRefund($this->event);
    }

    /**
     * Stripe can deliver the refund webhook before revertPayment has saved our refund row. Without a backoff
     * the five tries are spent immediately, all failing to find that row.
     */
    public function backoff() {
        return [5, 15, 30, 60];
    }

    public function uniqueId() {
        return $this->event->data->object->id;
    }
}
