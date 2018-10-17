<?php
namespace App\Http\Repos;

use App\Payment;
use Illuminate\Support\Facades\DB;

class PaymentRepo {

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
}
?>
