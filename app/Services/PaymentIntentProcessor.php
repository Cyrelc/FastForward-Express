<?php

namespace App\Services;

use App\Http\Repos;

use Illuminate\Support\Facades\DB;

class PaymentIntentProcessor {
    private $ORDERED_PAYMENT_INTENT_STATUSES = [
        NULL,
        'pending', //custom status used when created, when there is no status from Stripe yet. As such receives lowest priority
        'created',
        'requires_payment_method',
        'requires_confirmation',
        'processing',
        'succeeded',
        'requires_capture',
        'requires_action',
        'canceled',
    ];

    public function ProcessPaymentIntent($event) {
        activity('jobs')->log('Processing payment intent update for ' . $event->data->object->id);
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        DB::beginTransaction();

        $paymentIntent = $event->data->object;
        try {
            $card = $paymentIntent->charges->data[0]->payment_method_details->card;
            $paymentType = $paymentRepo->GetPaymentTypeByName($card->brand);
        } catch (\Throwable $e) {
            $card = null;
            $paymentType = $paymentRepo->GetPaymentTypeByName('Stripe (Pending)');
        }

        $payments = $paymentRepo->GetPaymentsByPaymentIntentId($paymentIntent->id);

        foreach($payments as $payment) {
            // only if we are 'upgrading' the status
            // this means we don't process 'created' before 'success' but also means we never accidentally process the same payment twice. 
            // which the stripe API makes a possibility. They do not guarantee idempotence, so instead this does
            $oldStatus = explode('.', $payment->payment_intent_status);
            $oldStatus = end($oldStatus);
            $oldStatusIndex = array_search($oldStatus, $this->ORDERED_PAYMENT_INTENT_STATUSES);
            $newStatus = explode('.', $event->type);
            $newStatus = end($newStatus);
            $newStatusIndex = array_search($newStatus, $this->ORDERED_PAYMENT_INTENT_STATUSES);
            if($oldStatusIndex && $newStatusIndex && $oldStatusIndex < $newStatusIndex) {
                $paymentRepo->UpdatePaymentIntentStatus($paymentIntent->id, $newStatus);

                $paymentRepo->Update($payment->payment_id, [
                    'amount' => $payment->amount,
                    'payment_type_id' => $paymentType->payment_type_id,
                    'reference_value' => $card ? '**** **** **** ' . $card->last4 : null,
                ]);

                if($newStatus == 'succeeded')
                    $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, -(Decimal($payment->amount) / Decimal(100)));
                if($newStatus == 'canceled')
                    $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, Decimal($payment->amount) / Decimal(100));
            } else {
                activity('jobs')
                    ->log('[ReceiveStripeWebhook.handle] skipped. Previous payment intent status: ' . $payment->payment_intent_status . '. New payment intent status: ' . $event->type);
            }
        }

        DB::commit();
    }
}

?>
