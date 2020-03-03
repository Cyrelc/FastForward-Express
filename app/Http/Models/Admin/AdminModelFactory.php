<?php
    namespace App\Http\Models\Admin;
    
	use App\Http\Repos;
    use App\Http\Models\Admin;

    class AdminModelFactory{
        public function GetAppSettingsModel() {
            $paymentRepo = new Repos\PaymentRepo();
            $ratesheetRepo = new Repos\RatesheetRepo();

            $model = new AppSettingsFormModel();

            $model->gst = config('ffe_config.gst');
            $model->paymentTypes = $paymentRepo->GetPaymentTypes();
            $model->ratesheets = $ratesheetRepo->ListAllNameAndId();
            return $model;
        }
    }
?>
