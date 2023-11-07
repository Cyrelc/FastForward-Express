<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Http\Controllers\WebhookController;

class ReceiveStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle()
    {
        activity('jobs')->log('Processing pending job for Stripe transaction ' . $this->event->data->object->id);
        $controller = new WebhookController;
        $controller->ProcessPaymentIntentUpdate($this->event);
    }
}
