<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\PaymentIntentProcessor;

class ReceiveStripeWebhook implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $uniqueFor = 600;

    protected $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event)
    {
        activity('jobs')->log('Creating pending job for Stripe transaction ' . $event->data->object->id);
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(PaymentIntentProcessor $processor)
    {
        activity('jobs')->log('Handling pending job for Stripe transaction: ' . $this->event->data->object->id);
        $processor->ProcessPaymentIntent($this->event);
    }

    public function backoff() {
        return [2, 10, 20];
    }
}
