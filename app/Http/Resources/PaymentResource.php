<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

use App\Http\Payment;

class PaymentResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $payment = [
            'amount' => $this->amount,
            'comment' => $this->comment,
            'date' => $this->date,
            'error' => $this->error,
            'invoice_id' => $this->invoice_id,
            'stripe_object_type' => $this->stripe_object_type,
            'stripe_status' => $this->stripe_status,
            'payment_type' => $this->payment_type->name,
            'payment_id' => $this->payment_id,
            'receipt_url' => $this->receipt_url,
            'reference_value' => $this->reference_value,
            'is_stripe_transaction' => $this->isStripeTransaction(),
        ];

        if(Auth::user()->can('revertAny', Payment::class)) {
            $payment['stripe_payment_intent_id'] = $this->stripe_payment_intent_id;
            $payment['stripe_refund_id'] = $this->stripe_refund_id;
            $payment['can_be_reverted'] = Auth::user()->can('revert', $this->resource) && $this->can_be_reverted;
        }

        return $payment;
    }
}
