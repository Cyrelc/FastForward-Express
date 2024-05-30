<?php
namespace App\Http\Repos;

use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentRepo {
    public function GetById($id) {
        $payment = Payment::where('payment_id', $id)->first();

        return $payment;
    }

    public function GetByInvoiceId($invoiceId) {
        $payments = Payment::where('invoice_id', $invoiceId)
            ->leftJoin('payment_types', 'payment_types.payment_type_id', '=', 'payments.payment_type_id')
            ->select(array_merge(
                [
                    'amount',
                    'comment',
                    'date',
                    'error',
                    'invoice_id',
                    'stripe_status',
                    'payment_types.name as payment_type',
                    'payments.payment_id',
                    'receipt_url',
                    'reference_value',
                    DB::raw('case when stripe_id is null then false else true end as is_stripe_transaction'),
                ],
                Auth::user()->can('undo', Payment::class) ? ['stripe_id'] : []
            ));

        return $payments->get();
    }

    public function GetPaymentsByAccountId($accountId) {
        $payments = Payment::where('payments.account_id', $accountId)
            ->leftJoin('payment_types', 'payments.payment_type_id', '=', 'payment_types.payment_type_id')
            ->leftJoin('invoices', 'payments.invoice_id', 'invoices.invoice_id')
            ->select(array_merge(
                [
                    'amount',
                    'comment',
                    'error',
                    'invoices.bill_end_date as invoice_date',
                    'payment_id',
                    'payment_types.name as payment_type',
                    'stripe_status',
                    'payments.invoice_id',
                    'payments.date',
                    'payments.payment_type_id',
                    DB::raw('case when stripe_id is null then false else true end as is_stripe_transaction'),
                    'reference_value',
                ],
                Auth::user()->can('undo', Payment::class) ? ['stripe_id'] : []
            ));

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

    public function GetPaymentsByPaymentIntentId($paymentIntentId) {
        $payment = Payment::where('stripe_id', $paymentIntentId);

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
        $updatedPayment = Payment::firstOrFail($paymentId)->update($payment);

        return $updatedPayment;
    }
}
?>
