<?php

namespace App\Services;

// use App\Http\Repos;
use App\Models\Payment;
use App\Models\PaymentTypes;

use App\Models\Invoice;
use App\Http\Repos\InvoiceRepo;
use Illuminate\Support\Facades\DB;

class StripeRefundProcessor {
    private $ORDERED_REFUND_STATUSES = [
        NULL,
        'pending', //custom status used when created, when there is no status from Stripe yet. As such receives lowest priority
        'requires_action',
        'succeeded',
        'failed',
        'canceled',
    ];

    public function processRefund($event) {
        $refund = $event->refunds->data[0];
        activity('jobs')->log('Processing refund for ' . $refund->id);

        DB::beginTransaction();

        $payment = Payment::where('stripe_refund_id', $refund->id)->firstOrFail();

        $oldStatusIndex = array_search($payment->stripe_status, $this->ORDERED_REFUND_STATUSES);
        $newStatusIndex = array_search($refund->status, $this->ORDERED_REFUND_STATUSES);

        if($oldStatusIndex == false || $newStatusIndex == false) {
            activity('payment_intent')
            ->performedOn($payment)
            ->event('error')
            ->withProperties([
                'event' => $event,
                'old_status' => $oldStatus,
                'old_status_index' => $oldStatusIndex,
                'newStatus' => $paymentIntent->status ?? null,
                'new_status_index' => $newStatusIndex
            ])->log(['[StripeRefundProcessor.processRefund] invalid status found']);
        } else if($newStatusIndex > $oldStatusIndex) {
            $paymentAmount = bcdiv($refund->amount, 100, 2);

            $payment->update([
                'error' => $event->failure_message ?? null,
                'stripe_status' => $refund->status,
                'receipt_url' => $event->receipt_url,
            ]);

            if($newStatus == 'succeeded') {
                $invoice = Invoice::find($payment->invoice_id);
                $invoiceRepo = new InvoiceRepo();

                activity('stripe')
                    ->performedOn($payment)
                    ->withProperties([
                        'stripe_payment_intent_id' => $refund->id,
                        'webhook_status' => $refund->status,
                        'amount' => -$paymentAmount,
                        'receipt_url' => $event->receipt_url
                    ])->log('[processRefund] succeeded.');

                $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, -$paymentAmount);
            }
        } else {
            activity('jobs')
                ->performedOn($payment)
                ->withProperties(['stripe_payment_intent_id' => $paymentIntent->id, 'database_status' => $payment->stripe_status, 'webhook_status' => $paymentIntent->status])
                ->log('[ReceiveStripeWebhook.handle] skipped.');
        }

        DB::commit();
    }
}

?>
