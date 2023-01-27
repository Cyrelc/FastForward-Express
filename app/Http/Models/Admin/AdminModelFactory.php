<?php

namespace App\Http\Models\Admin;

use App\Http\Repos;
use App\Http\Models\Admin;

class AdminModelFactory{
    public function GetAppSettingsModel() {
        $applicationSettingsRepo = new Repos\ApplicationSettingsRepo();
        $interlinerRepo = new Repos\InterlinerRepo();
        $paymentRepo = new Repos\PaymentRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();

        $model = new AppSettingsFormModel();

        $model->gst = config('ffe_config.gst');
        $model->interliners = $interlinerRepo->ListAll();
        $model->payment_types = $paymentRepo->GetPaymentTypes();
        $model->ratesheets = $ratesheetRepo->ListAllNameAndId();
        $model->blocked_dates = $applicationSettingsRepo->GetByType('blocked_date');

        return $model;
    }

    public function GetAccountsReceivableModel($startDate, $endDate) {
        $billRepo = new Repos\BillRepo();
        $accountRepo = new Repos\AccountRepo();

        $model = new \stdClass();
        $model->accounts_receivable = $accountRepo->GetAccountsReceivable($startDate, $endDate);

        $prepaidAccountsReceivable = $billRepo->GetPrepaidAccountsReceivable($startDate, $endDate);
        foreach($prepaidAccountsReceivable as $prepaid) {
            $model->accounts_receivable[] = [
                'account_number' => $prepaid->payment_type_name,
                'name' => $prepaid->payment_type_name,
                'total_cost' => $prepaid->amount,
                'type' => 'Prepaid'
            ];
        }

        return $model;
    }
}

?>
