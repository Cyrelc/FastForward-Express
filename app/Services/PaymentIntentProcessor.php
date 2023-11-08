<?php

namespace App\Services;

use App\Http\Repos;

use Illuminate\Support\Facades\DB;

class PaymentIntentProcessor {
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

    public function ProcessPaymentIntent($event) {
        activity('jobs')->log('Processing payment intent update for ' . $event->data->object->id);
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        activity('jobs')->log('Before begin transaction');
        DB::beginTransaction();

        $paymentIntent = $event->data->object;
        activity('jobs')->log('after PaymentIntent declaration');
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
            if(array_search($payment->payment_intent_status, $this->ORDERED_PAYMENT_INTENT_STATUSES) < array_search($event->type, $this->ORDERED_PAYMENT_INTENT_STATUSES)) {
                $paymentRepo->UpdatePaymentIntentStatus($paymentIntent->id, $event->type);

                $paymentRepo->Update($payment->payment_id, [
                    'amount' => $payment->amount,
                    'payment_type_id' => $paymentType->payment_type_id,
                    'reference_value' => $card ? $card->last4 : null,
                ]);

                if($event->type == 'payment_intent.succeeded')
                    $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, -$payment->amount);
                if($event->type == 'payment_intent.cancelled')
                    $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, $payment->amount);
            } else {
                activity('jobs')
                    ->log('[ReceiveStripeWebhook.handle] skipped. Previous payment intent status: ' . $payment->payment_intent_status . '. New payment intent status: ' . $event->type);
            }
        }

        DB::commit();
    }
}

?>
