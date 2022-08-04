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

    public function GetPaymentsByAccountId($accountId) {
        $payments = Payment::where('payments.account_id', $accountId)
            ->leftJoin('payment_types', 'payments.payment_type_id', '=', 'payment_types.payment_type_id')
            ->leftJoin('invoices', 'payments.invoice_id', 'invoices.invoice_id')
            ->select(
                'payment_id',
                'payments.invoice_id',
                'invoices.bill_end_date as invoice_date',
                'payments.date',
                'amount',
                'payments.payment_type_id',
                'payment_types.name as payment_type',
                'reference_value',
                'comment'
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

    public function GetPaymentType($paymentTypeId) {
        $payment_type = PaymentType::where('payment_type_id', $paymentTypeId);

        return $payment_type->first();
    }

    public function GetPaymentTypeByName($paymentTypeName) {
        $payment_type = PaymentType::where('name', $paymentTypeName);

        return $payment_type->first();
    }

    public function GetPaymentTypes() {
        $payment_types = PaymentType::All();

        return $payment_types;
    }

    public function GetPaymentTypesList() {
        $paymentTypes = PaymentType::select('name as label', 'payment_type_id as value');

        return $paymentTypes->get();
    }

    public function GetPaymentTypesForAccounts() {
        $paymentTypes = PaymentType::where('is_prepaid', true)
            ->orWhere('name', 'Account');

        return $paymentTypes->get();
    }

    public function GetPrepaidPaymentTypes() {
        $paymentTypes = PaymentType::where('is_prepaid', true);

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

    public function Update($payment_id, $payment) {
        $old = Payment::where('payment_id', $payment_id)->first();
        $fields = array('amount', 'payment_type_id', 'reference_value');

        foreach($fields as $field)
            $old->$field = $payment[$field];

        $old->save();
        return $old;
    }
}
?>
