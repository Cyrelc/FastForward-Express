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
        $refund = $event->data->object;
        activity('jobs')->log('Processing refund for ' . $refund->id);

        DB::beginTransaction();

        try {
            $payment = Payment::where('stripe_refund_id', $refund->id)->firstOrFail();

            $oldStatusIndex = array_search($payment->stripe_status, $this->ORDERED_REFUND_STATUSES);
            $newStatusIndex = array_search($refund->status, $this->ORDERED_REFUND_STATUSES);

            // Strict === false: a NULL prior status legitimately lives at index 0,
            // which a loose == false would wrongly flag as "not found".
            if($oldStatusIndex === false || $newStatusIndex === false) {
                activity('payment_intent')
                ->performedOn($payment)
                ->event('error')
                ->withProperties([
                    'event' => $event,
                    'old_status' => $payment->stripe_status,
                    'old_status_index' => $oldStatusIndex,
                    'new_status' => $refund->status ?? null,
                    'new_status_index' => $newStatusIndex
                ])->log(['[StripeRefundProcessor.processRefund] invalid status found']);
            } else if($newStatusIndex > $oldStatusIndex) {
                $paymentAmount = bcdiv($refund->amount, 100, 2);

                $payment->update([
                    'error' => $refund->failure_message ?? null,
                    'stripe_status' => $refund->status,
                ]);

                if($refund->status == 'succeeded') {
                    $invoice = Invoice::find($payment->invoice_id);
                    $invoiceRepo = new InvoiceRepo();

                    activity('stripe')
                        ->performedOn($payment)
                        ->withProperties([
                            'stripe_payment_intent_id' => $refund->payment_intent,
                            'webhook_status' => $refund->status,
                            'amount' => -$paymentAmount,
                            'refund_id' => $refund->id,
                        ])->log('[processRefund] succeeded.');

                    $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, -$paymentAmount);
                }
            } else {
                activity('jobs')
                    ->performedOn($payment)
                    ->withProperties(['refund_id' => $refund->id, 'database_status' => $payment->stripe_status, 'webhook_status' => $refund->status])
                    ->log('[processRefund] skipped.');
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            activity('jobs')
                ->event('error')
                ->withProperties(['refund_event' => $event])
                ->log('[StripeRefundProcessor.processRefund] failed');

            throw $e;
        }
    }
}

?>
