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
        'requires_capture',
        'requires_action',
        'succeeded',
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
            $oldStatus = str_replace('payment_intent.', '', $payment->payment_intent_status);
            $oldStatusIndex = array_search($oldStatus, $this->ORDERED_PAYMENT_INTENT_STATUSES);
            $newStatus = str_replace('payment_intent.', '', $event->data->status);
            $newStatusIndex = array_search($newStatus, $this->ORDERED_PAYMENT_INTENT_STATUSES);

            if($oldStatusIndex == false || $newStatusIndex == false) {
                activity('payment_intent')
                    ->performedOn($payment)
                    ->withProperties(['new_status_index' => $newStatusIndex, 'old_status_index' => $oldStatusIndex, 'new_status' => $event->data->status, 'old_status' => $payment->payment_intent_status])
                    ->log(['[ReceiveStripeWebhook.handle] invalid status found']);
            } else if($newStatusIndex > $oldStatusIndex) {
                $paymentAmount = bcdiv($paymentIntent->amount_received, 100, 2);

                $payment->update([
                    'amount' => $paymentAmount,
                    'error' => $paymentIntent->last_payment_error ? $paymentIntent->last_payment_error->message : null,
                    'payment_intent_status' => $newStatus,
                    'payment_type_id' => $paymentType->payment_type_id,
                    'reference_value' => $card ? '**** **** **** ' . $card->last4 : null,
                ]);

                if($newStatus == 'succeeded') {
                    activity('payment_intent')
                        ->performedOn($payment)
                        ->withProperties(['payment_intent_id' => $paymentIntent->id, 'webhook_status' => $event->data->status, 'amount' => $paymentAmount])
                        ->log('[ReceiveStripeWebhook.handle] succeeded');
                    if($invoice->balance_owing == 0)
                        throw new \Exception('Attempting to double pay invoice #' . $payment->invoice_id, $event);
                    $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, -$paymentAmount);
                }
            } else {
                activity('jobs')
                    ->performedOn($payment)
                    ->withProperties(['payment_intent_id' => $paymentIntent->id, 'database_status' => $payment->payment_intent_status, 'webhook_status' => $event->data->status])
                    ->log('[ReceiveStripeWebhook.handle] skipped.');
            }
        }

        DB::commit();
    }
}

?>
