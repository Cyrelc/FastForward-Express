<?php

namespace App\Http\Collectors;

class PaymentTypeCollector {
    public function CollectMultiple($req) {
        $paymentTypes = [];
        foreach($req->paymentTypes as $paymentType) {
            array_push($paymentTypes, $paymentType);
        }
    }
}

?>
