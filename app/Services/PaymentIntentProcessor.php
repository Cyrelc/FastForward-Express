<?php

namespace App\Services;

use App\Http\Repos;

use App\Models\Invoice;
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
        $paymentIntent = $event->data->object;
        activity('jobs')->log('Processing payment intent update for ' . $paymentIntent->id);
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        DB::beginTransaction();

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
            try {
                $oldStatus = str_replace('payment_intent.', '', $payment->stripe_status);
                $oldStatusIndex = array_search($oldStatus, $this->ORDERED_PAYMENT_INTENT_STATUSES);
                $newStatus = str_replace('payment_intent.', '', $paymentIntent->status);
                $newStatusIndex = array_search($newStatus, $this->ORDERED_PAYMENT_INTENT_STATUSES);

                if($oldStatusIndex == false || $newStatusIndex == false) {
                    activity('stripe')
                        ->performedOn($payment)
                        ->event('error')
                        ->withProperties([
                            'event' => $event,
                            'old_status' => $oldStatus,
                            'old_status_index' => $oldStatusIndex,
                            'newStatus' => $paymentIntent->status ?? null,
                            'new_status_index' => $newStatusIndex
                        ])->log(['[ReceiveStripeWebhook.handle] invalid status found']);
                } else if($newStatusIndex > $oldStatusIndex) {
                    $paymentAmount = bcdiv($paymentIntent->amount_received, 100, 2);

                    $payment->update([
                        'amount' => $paymentAmount,
                        'error' => $paymentIntent->last_payment_error ? $paymentIntent->last_payment_error->message : null,
                        'stripe_status' => $newStatus,
                        'payment_type_id' => $paymentType->payment_type_id,
                        'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null,
                        'reference_value' => $card ? '**** **** **** ' . $card->last4 : null,
                    ]);

                    if($newStatus == 'succeeded') {
                        $invoice = Invoice::find($payment->invoice_id);
                        activity('stripe')
                            ->performedOn($payment)
                            ->withProperties([
                                'stripe_payment_intent_id' => $paymentIntent->id,
                                'webhook_status' => $paymentIntent->status,
                                'amount' => $paymentAmount,
                                'receipt_url' => $paymentIntent->charges->data[0]->receipt_url
                            ])->log('[ReceiveStripeWebhook.handle] succeeded');
                        if($invoice->balance_owing == 0)
                            report(new \Exception('Attempting to double pay invoice #' . $payment->invoice_id));
                        else
                            $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, -$paymentAmount);
                    }
                } else {
                    activity('jobs')
                        ->performedOn($payment)
                        ->withProperties(['stripe_payment_intent_id' => $paymentIntent->id, 'database_status' => $payment->stripe_status, 'webhook_status' => $paymentIntent->status])
                        ->log('[ReceiveStripeWebhook.handle] skipped.');
                }
            } catch (\Throwable $e) {
                activity('jobs')
                    ->event('error')
                    ->performedOn($payment)
                    ->withProperties(['payment_intent_event' => $event])
                    ->log('[ReceiveStripeWebhook.handle] failed');

                throw $e;
            }
        }

        DB::commit();
    }
}

?>
