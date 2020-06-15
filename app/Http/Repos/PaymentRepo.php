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

    public function listPaymentsByAccount($account_id) {
        $payments = Payment::where('account_id', $account_id)
            ->leftJoin('payment_types', 'payments.payment_type_id', '=', 'payment_types.payment_type_id')
            ->select('payment_id',
                    'invoice_id',
                    'date',
                    DB::raw('format(amount, 2) as amount'),
                    'payments.payment_type_id',
                    'payment_types.name as payment_type',
                    'reference_value',
                    'comment');

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

    public function GetPaymentType($paymentTypeId) {
        $payment_type = PaymentType::where('payment_type_id', $paymentTypeId);

        return $payment_type->first();
    }

    public function GetPaymentTypes() {
        $payment_types = PaymentType::All();

        return $payment_types;
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
