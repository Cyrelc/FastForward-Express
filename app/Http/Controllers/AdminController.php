<?php

    namespace App\Http\Controllers;

    use Artisan;
    use Config;
    use DB;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;

    use App\Http\Models\Admin;
    use App\Http\Models\Chart;
    use App\Http\Repos;

    Class AdminController extends Controller {

        public function getChart(Request $req) {
            try {
                $chartModelFactory = new Chart\ChartModelFactory();
                $type = $req->type;
                if($type) {
                    $model = $chartModelFactory->GetMonthlyBills($req->dateGroupBy, $req->startDate, $req->endDate, $req->groupBy, $req->summationType);
                    return json_encode($model);
                } else {
                    return view('admin.charts');
                }
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        }

        public function getModel() {
            $adminModelFactory = new Admin\AdminModelFactory();
            $model = $adminModelFactory->GetAppSettingsModel();
            return json_encode($model);
        }

        public function view() {
            return view('admin.appSettings');
        }

        public function store(Request $req) {
            DB::beginTransaction();
            try {
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
            } catch (Exception $e) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        public function hashPassword(Request $req) {
            return Hash::make(preg_replace('/\s+/', '', $req->password));
        }
    }
?>
