<?php

namespace App\Http\Repos;

use App\Amendment;

class AmendmentRepo {
    public function Delete($amendmentId) {
        $amendment = Amendment::where('amendment_id', $amendmentId)->first();

        $amendment->delete();
        return;
    }

    public function Insert($amendment) {
        $new = new Amendment;

        return ($new->create($amendment));
    }

    public function GetById($amendment_id) {
        $amendment = Amendment::where('amendment_id', $amendment_id);

        return $amendment->first();
    }

    public function GetByInvoiceId($invoice_id) {
        $amendments = Amendment::where('invoice_id', $invoice_id);

        return $amendments->get();
    }
}
