<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Http\Repos;
use Illuminate\Support\Facades\DB;

class ReceiveStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = 10;

    protected $event;
    private $ORDERED_PAYMENT_INTENT_STATUSES = [
        NULL,
        'payment_intent.pending', //custom status used when created, when there is no status from Stripe yet. As such receives lowest priority
        'payment_intent.created',
        'payment_intent.requires_payment_method',
        'payment_intent.requires_confirmation',
        'payment_intent.processing',
        'payment_intent.succeeded',
        'payment_intent.requires_capture',
        'payment_intent.requires_action',
        'payment_intent.canceled',
    ];

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
        activity('jobs')->log('Processing payment intent update for ' . $this->event->data->object->id);
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        DB::beginTransaction();

        $paymentIntent = $this->event->data->object;
        try {
            $card = $paymentIntent->charges->data[0]->payment_method_details->card;
            $paymentType = $paymentRepo->GetPaymentTypeByName($card->brand);
            activity('jobs')->log('Card type: ' . $card);
        } catch (\Throwable $e) {
            $card = null;
            $paymentType = $paymentRepo->GetPaymentTypeByName('Stripe (Pending)');
            activity('jobs')->log('Card type: ' . $card);
        }

        $payments = $paymentRepo->GetPaymentsByPaymentIntentId($paymentIntent->id);

        foreach($payments as $payment) {
            activity('jobs')->log('Payment: ' . $payment->payment_id . '. Payment Intent: ' . $payment->payment_intent_id);
            activity('jobs')->log('Previous status: ' . $payment->payment_intent_status . '. New status: ' . $event->type); 
            activity('jobs')->log('Previous status: ' . array_search($payment->payment_intent_status, $this->ORDERED_PAYMENT_INTENT_STATUSES) . '. New status: ' . array_search($event->type, $this->ORDERED_PAYMENT_INTENT_STATUSES));
            // only if we are 'upgrading' the status
            // this means we don't process 'created' before 'success' but also means we never accidentally process the same payment twice. 
            // which the stripe API makes a possibility. They do not guarantee idempotence, so instead this does
            if(array_search($payment->payment_intent_status, $this->ORDERED_PAYMENT_INTENT_STATUSES) < array_search($this->event->type, $this->ORDERED_PAYMENT_INTENT_STATUSES)) {
                $paymentRepo->UpdatePaymentIntentStatus($paymentIntent->id, $this->event->type);

                if($payment->payment_type_id == $stripePendingPaymentType->payment_type_id && $newPaymentType) {
                    $paymentRepo->Update($payment->payment_id, [
                        'amount' => $payment->amount,
                        'payment_type_id' => $newPaymentType->payment_type_id,
                        'reference_value' => $card ? $card->last4 : null,
                    ]);
                }
                if($this->event->type == 'payment_intent.succeeded')
                    $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, -$payment->amount);
                if($this->event->type == 'payment_intent.cancelled')
                    $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, $payment->amount);
            } else {
                activity('jobs')
                    ->log('[ReceiveStripeWebhook.handle] skipped. Previous payment intent status: ' . $payment->payment_intent_status . '. New payment intent status: ' . $event->type);
            }
        }

        DB::commit();
    }
}
