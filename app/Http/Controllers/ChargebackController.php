<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Http\Repos;
use App\Http\Models\Chargeback;

class ChargebackController extends Controller {
    public function buildTable() {
        $chargebackRepo = new Repos\ChargebackRepo();
        $chargebacks = $chargebackRepo->ListAll();
        return json_encode($chargebacks);
    }

    public function delete(Request $req, $chargebackId) {
        DB::beginTransaction();

        $chargebackRepo = new Repos\ChargebackRepo();
        $chargebackRepo->delete($chargebackId);

        DB::commit();
        return;
    }

    public function store(Request $req) {
        DB::beginTransaction();
        $chargebackRules = new \App\Http\Validation\ChargebackValidationRules();
        $rules = $chargebackRules->GetValidationRules();
        $this->validate($req, $rules['rules'], $rules['messages']);

        $chargebackRepo = new Repos\ChargebackRepo();

        if($req->chargeback_id)
            $chargebackRepo->Update($req);
        else
            $chargebackRepo->CreateChargebacks($req);

        DB::commit();

        return;
    }
}
