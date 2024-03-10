<?php

namespace App\Http\Controllers;

use Artisan;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

use App\Http\Models\Admin;
use App\Http\Models\Chart;
use App\Http\Repos;
use App\Http\Collectors\SelectionCollector;

Class AdminController extends Controller {
    public function deleteAppSetting(Request $req, $appSettingId) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $appSettingRepo = new Repos\ApplicationSettingsRepo();
        $appSettingRepo->Delete($appSettingId);

        $adminModelFactory = new Admin\AdminModelFactory();
        $model = $adminModelFactory->GetAppSettingsModel();

        return json_encode($model);
    }

    public function getAccountsPayable(Request $req) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $startDate = new \DateTime($req->start_date);
        $endDate = new \DateTime($req->end_date);

        $adminModelFactory = new Admin\AdminModelFactory();
        $model = $adminModelFactory->GetAccountsPayableModel($startDate, $endDate);

        return json_encode($model);
    }

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

    public function getSelections(Request $req) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $selectionsRepo = new Repos\SelectionsRepo();

        $selections = $selectionsRepo->List();
        return json_encode($selections);
    }

    public function storeAccountingSettings(Request $req) {
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

        // Config::set('ffe_config.gst', (float)$req->gst);
        // config(['ffe_config.gst' => (float)$req->gst]);
        // Artisan::call('config:cache');

        DB::commit();

        return response()->json([
            'success' => true,
        ]);
    }

    public function storeBlockedDate(Request $req) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $appSettingRepo = new Repos\ApplicationSettingsRepo();
        $appSettingCollector = new \App\Http\Collectors\ApplicationSettingCollector();

        $blockedDate = $appSettingCollector->CollectBlockedDate($req);
        DB::beginTransaction();

        $appSettingRepo->Insert($blockedDate);

        DB::commit();

        return response()->json([
            'success' => true,
            'blocked_dates' => $appSettingRepo->GetByType('blocked_date')
        ]);
    }

    public function storeSelection(Request $req) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $selectionsValidationRules = new \App\Http\Validation\SelectionValidationRules();
        $validation = $selectionsValidationRules->GetValidationRules();

        $this->validate($req, $validation['rules'], $validation['messages']);

        DB::beginTransaction();
        $selectionsCollector = new SelectionCollector();
        $selection = $selectionsCollector->Collect($req);

        $selectionsRepo = new Repos\SelectionsRepo();
        $selectionsRepo->Insert($selection);
        DB::commit();

        $selections = $selectionsRepo->List();
        return json_encode($selections);
    }
}

?>
