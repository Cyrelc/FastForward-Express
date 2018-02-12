<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Http\Repos;
use App\Http\Models\Chargeback;

class ChargebackController extends Controller {
    public function manage() {
        // Check permissions
        $ChargebackModelFactory = new Chargeback\ChargebackModelFactory();
        $model = $ChargebackModelFactory->GetCreateModel();
        return view('chargebacks.manage', compact('model'));
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try {
            $chargebackRules = new \App\Http\Validation\ChargebackValidationRules();
            $rules = $chargebackRules->GetValidationRules($req);
            $this->validate($req, $rules['rules'], $rules['messages']);

            $chargebackRepo = new Repos\ChargebackRepo();

            $chargebackRepo->CreateChargebacks($req);

            DB::commit();

            return;
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
