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
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId);

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
