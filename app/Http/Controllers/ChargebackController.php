<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Http\Repos;
use App\Http\Models\Chargeback;

class ChargebackController extends Controller {
    public function buildTable(Request $req) {
        if($req->user()->cannot('viewAny', Chargeback::class))
            abort(403);

        $chargebackRepo = new Repos\ChargebackRepo();
        $chargebacks = $chargebackRepo->ListAll();

        return json_encode($chargebacks);
    }

    public function delete(Request $req, $chargebackId) {
        if($req->user()->cannot('delete', Chargeback::class))
            abort(403);

        DB::beginTransaction();

        $chargebackRepo = new Repos\ChargebackRepo();
        $chargebackRepo->delete($chargebackId);

        DB::commit();
        return;
    }

    public function store(Request $req) {
        $chargebackRepo = new Repos\ChargebackRepo();
        if($req->chargeback_id) {
            $chargeback = $chargebackRepo->GetById($req->chargeback_id);
            if($req->user()->cannot('update', $chargeback))
                abort(403);
        } else
            if($req->user()->cannot('create', Chargeback::class))
                abort(403);

        DB::beginTransaction();
        $chargebackRules = new \App\Http\Validation\ChargebackValidationRules();
        $rules = $chargebackRules->GetValidationRules();
        $this->validate($req, $rules['rules'], $rules['messages']);


        if($req->chargeback_id)
            $chargebackRepo->Update($req);
        else
            $chargebackRepo->CreateChargebacks($req);

        DB::commit();

        return;
    }
}
