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

    public function edit() {
        $chargebackModelFactory = new Chargeback\ChargebackModelFactory();
        $model = $chargebackModelFactory->GetEditModel();
        return view('chargebacks.edit', compact('model'));
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try {
            $chargebackRules = new \App\Http\Validation\ChargebackValidationRules();
            $rules = $chargebackRules->GetValidationRules();
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

    public function update(Request $req, $id) {
        DB::beginTransaction();
        try{ 
            $chargebackRules = new \App\Http\Validation\ChargebackValidationRules();
            $rules = $chargebackRules->GetValidationRules();
            $this->validate($req, $rules['rules'], $rules['messages']);

            $chargebackRepo = new Repos\ChargebackRepo();

            $chargebackRepo->Update($id, $req);

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

    public function deactivate($id) {
        DB::beginTransaction();
        try{
            //check permissions
            //check if object exists
            $chargebackRepo = new Repos\ChargebackRepo();

            $chargebackRepo->DeactivateById($id);

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
