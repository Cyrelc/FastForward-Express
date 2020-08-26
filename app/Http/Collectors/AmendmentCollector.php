<?php

namespace App\Http\Collectors;

class AmendmentCollector {
    public function Collect($req) {
        return [
            'amendment_id' => $req->amendment_id ? $req->amendment_id : null,
            'amount' => $req->amount,
            'bill_id' => $req->bill_id,
            'description' => $req->description,
            'invoice_id' => $req->invoice_id
        ];
    }
}

