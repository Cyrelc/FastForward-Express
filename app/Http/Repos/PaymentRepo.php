<?php
namespace App\Http\Repos;

use App\Payment;
use Illuminate\Support\Facades\DB;

class PaymentRepo {

    public function listPaymentsByAccount($account_id) {
        $payments = Payment::where('account_id', $account_id);

        return $payments->get();
    }

    public function Insert($payment) {
    	$new = new Payment;

        return ($new->create($payment));
    }
}
?>
