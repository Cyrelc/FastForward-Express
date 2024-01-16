<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Http\Repos;
use App\Http\Models\Chargeback;

class ChargebackController extends Controller {
    public function delete(Request $req, $chargebackId) {
        $chargebackRepo = new Repos\ChargebackRepo();

        $chargeback = $chargebackRepo->GetById($chargebackId);

        if($req->user()->cannot('delete', $chargeback))
            abort(403);

        DB::beginTransaction();

        $chargebackRepo->delete($chargebackId);

        DB::commit();
        return;
    }

    public function index(Request $req) {
        if($req->user()->cannot('viewAny', Chargeback::class))
            abort(403);

        $chargebackRepo = new Repos\ChargebackRepo();
        $queryRepo = new Repos\QueryRepo();

        $chargebacks = $chargebackRepo->ListAll($req);
        $queries = $queryRepo->GetByTable('chargebacks');

        return response()->json([
            'success' => true,
            'data' => $chargebacks,
            'queries' => $queries
        ]);
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
