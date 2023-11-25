<?php
namespace App\Http\Repos;

use App\Payment;
use App\PaymentType;
use Illuminate\Support\Facades\DB;

class PaymentRepo {

    public function GetById($id) {
        $payment = Payment::where('payment_id', $id)->first();

        return $payment;
    }

    public function GetByInvoiceId($invoiceId) {
        $payments = Payment::where('invoice_id', $invoiceId)
            ->leftJoin('payment_types', 'payment_types.payment_type_id', '=', 'payments.payment_type_id')
            ->select(
                'amount',
                'comment',
                'date',
                'invoice_id',
                'payment_intent_status',
                'payment_types.name as payment_type',
                'payments.payment_id',
                'reference_value',
                DB::raw('case when payment_intent_id is null then false else true end as is_stripe_transaction'),
            );

        return $payments->get();
    }

    public function GetPaymentsByAccountId($accountId) {
        $payments = Payment::where('payments.account_id', $accountId)
            ->leftJoin('payment_types', 'payments.payment_type_id', '=', 'payment_types.payment_type_id')
            ->leftJoin('invoices', 'payments.invoice_id', 'invoices.invoice_id')
            ->select(
                'amount',
                'comment',
                'invoices.bill_end_date as invoice_date',
                'payment_id',
                'payment_types.name as payment_type',
                'payment_intent_status',
                'payments.invoice_id',
                'payments.date',
                'payments.payment_type_id',
                DB::raw('case when payment_intent_id is null then false else true end as is_stripe_transaction'),
                'reference_value',
            );

        return $payments->get();
    }

    public function Insert($payment) {
    	$new = new Payment;

        return ($new->create($payment));
    }

    public function Delete($payment_id) {
        $payment = Payment::where('payment_id', $payment_id)->first();

        $payment->delete();
        return;
    }

    public function GetAccountPaymentType() {
        $paymentType = PaymentType::where('name', 'Account');

        return $paymentType->first();
    }

    public function GetIncompletePaymentIntents($invoiceId) {
        $stripePaymentTypeId = $this->GetPaymentTypeByName('Stripe (Pending)');

        $payment = Payment::where('invoice_id', $invoiceId)
            ->whereNotNull('payment_intent_id')
            ->where('payment_type_id', $stripePaymentTypeId->payment_type_id)
            ->whereNull('reference_value');

        return $payment->get();
    }

    public function GetPaymentsByPaymentIntentId($paymentIntentId) {
        $payment = Payment::where('payment_intent_id', $paymentIntentId);

        return $payment->get();
    }

    public function GetPaymentType($paymentTypeId) {
        $payment_type = PaymentType::where('payment_type_id', $paymentTypeId);

        return $payment_type->first();
    }

    public function GetPaymentTypeByName($paymentTypeName) {
        $payment_type = PaymentType::where('name', 'like', $paymentTypeName);

        return $payment_type->first();
    }

    public function GetPaymentTypes() {
        $payment_types = PaymentType::where('name', '!=', 'Stripe (Pending)');

        return $payment_types->get();
    }

    public function GetPaymentTypesList() {
        $paymentTypes = PaymentType::select('name as label', 'payment_type_id as value');

        return $paymentTypes->get();
    }

    public function GetPrepaidPaymentTypes() {
        $paymentTypes = PaymentType::where('type', 'prepaid');

        return $paymentTypes->get();
    }

    public function UpdatePaymentType($paymentType) {
        $old = PaymentType::where('payment_type_id', $paymentType['payment_type_id'])->first();

        $fields = array('default_ratesheet_id');

        foreach($fields as $field)
            $old->$field = $paymentType[$field];

        $old->save();

        return $old;
    }

    public function Update($paymentId, $payment) {
        $old = Payment::where('payment_id', $paymentId)->first();
        $fields = array('amount', 'payment_type_id', 'reference_value', 'payment_intent_status');

        foreach($fields as $field)
            if(isset($payment[$field]))
                $old->$field = $payment[$field];

        $old->save();
        return $old;
    }

    public function UpdatePaymentIntentStatus($paymentIntentId, $status) {
        $old = Payment::where('payment_intent_id', $paymentIntentId)
            ->update(['payment_intent_status' => $status]);

        return $old;
    }
}
?>
