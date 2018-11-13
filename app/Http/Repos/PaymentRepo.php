<?php
namespace App\Http\Repos;

use App\Payment;
use Illuminate\Support\Facades\DB;

class PaymentRepo {
    public function GetById($id) {
        $payment = Payment::where('payment_id', $id)->first();

        return $payment;
    }

    public function listPaymentsByAccount($account_id) {
        $payments = Payment::where('account_id', $account_id)
            ->select('payment_id',
                    'invoice_id',
                    'date',
                    DB::raw('format(amount, 2) as amount'),
                    'payment_type',
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

    public function Update($payment_id, $payment) {
        $old = Payment::where('payment_id', $payment_id)->first();
        $fields = array('amount', 'payment_type', 'reference_value');

        foreach($fields as $field)
            $old->$field = $payment[$field];

        $old->save();
        return $old;
    }
}
?>
