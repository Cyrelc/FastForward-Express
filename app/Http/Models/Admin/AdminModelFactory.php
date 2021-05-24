<?php

namespace App\Http\Models\Admin;

use App\Http\Repos;
use App\Http\Models\Admin;

class AdminModelFactory{
    public function GetAppSettingsModel() {
        $interlinerRepo = new Repos\InterlinerRepo();
        $paymentRepo = new Repos\PaymentRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();

        $model = new AppSettingsFormModel();

        $model->gst = config('ffe_config.gst');
        $model->interliners = $interlinerRepo->ListAll();
        $model->payment_types = $paymentRepo->GetPaymentTypes();
        $model->ratesheets = $ratesheetRepo->ListAllNameAndId();

        return $model;
    }

    public function GetAccountsReceivableModel($startDate, $endDate) {
        $billRepo = new Repos\BillRepo();
        $accountRepo = new Repos\AccountRepo();

        $model = new \stdClass();
        $model->accounts_receivable = $accountRepo->GetAccountsReceivable($startDate, $endDate);
        $model->prepaid_accounts_receivable = $billRepo->GetPrepaidAccountsReceivable($startDate, $endDate);

        return $model;
    }
}

?>
