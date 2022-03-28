<?php

namespace App\Http\Controllers;

use Artisan;
// use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Http\Models\Admin;
use App\Http\Models\Chart;
use App\Http\Repos;

Class AdminController extends Controller {
    public function getAccountsReceivable(Request $req, $startDate, $endDate) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $startDate = new \DateTime($startDate);
        $endDate = new \DateTime($endDate);
        $adminModelFactory = new Admin\AdminModelFactory();
        $model = $adminModelFactory->GetAccountsReceivableModel($startDate, $endDate);

        return json_encode($model);
    }

    public function getChart(Request $req) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);
        $chartModelFactory = new Chart\ChartModelFactory();
        $model = $chartModelFactory->GetMonthlyBills($req->dateGroupBy, $req->startDate, $req->endDate, $req->groupBy, $req->summationType);
        return json_encode($model);
    }

    public function getModel(Request $req) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $adminModelFactory = new Admin\AdminModelFactory();
        $model = $adminModelFactory->GetAppSettingsModel();
        return json_encode($model);
    }

    public function store(Request $req) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        DB::beginTransaction();

        $adminValidation = new \App\Http\Validation\AdminValidationRules();
        $temp = $adminValidation->GetPaymentTypeValidationRules($req);

        $validationRules = $temp['rules'];
        $validationMessages = $temp['messages'];

        $this->validate($req, $validationRules, $validationMessages);

        $paymentRepo = new Repos\PaymentRepo;

        foreach($req->paymentTypes as $paymentType)
            $paymentRepo->UpdatePaymentType($paymentType);

        Config::write('ffe_config.gst', (float)$req->gst);
        // we have to clear the config cache after writing
        Artisan::call('config:cache');

        DB::commit();

        return response()->json([
            'success' => true,
        ]);
    }

    public function hashPassword(Request $req) {
        return Hash::make(preg_replace('/\s+/', '', $req->password));
    }
}

?>
