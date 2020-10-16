<?php

	namespace App\Http\Models\Bill;

	use App\Http\Repos;
	use App\Http\Models\Bill;

	class BillModelFactory{

		public function ListAll() {
			$model = new BillsModel();

			try {
				$billsRepo = new Repos\BillRepo();
				$accountsRepo = new Repos\AccountRepo();
				$employeesRepo = new Repos\EmployeeRepo();
				$contactsRepo = new Repos\ContactRepo();

				$bills = $billsRepo->ListAll();

				$bill_view_models = Array();

				foreach ($bills as $bill){
					$bill_view_model = new BillViewModel();

					$bill_view_model->bill = $bill;
					$bill_view_model->account = $accountsRepo->GetById($bill->charge_account_id);
					if ($bill_view_model->account === null) {
                        $bill_view_model->account = new \App\Account();
                        $bill_view_model->account->name = "Cash";
                    }

					$bill_view_model->pickup_driver = $employeesRepo->GetById($bill->pickup_driver_id);
					$pickup_driver_contact = $contactsRepo->GetById($bill_view_model->pickup_driver->contact_id);
					$bill_view_model->pickup_driver_name = $pickup_driver_contact->first_name . ' ' . $pickup_driver_contact->last_name;

					$bill_view_model->delivery_driver = $employeesRepo->GetById($bill->delivery_driver_id);
					$delivery_driver_contact = $contactsRepo->GetById($bill_view_model->delivery_driver->contact_id);
					$bill_view_model->delivery_driver_name = $delivery_driver_contact->first_name . ' ' . $delivery_driver_contact->last_name;

					array_push($bill_view_models, $bill_view_model);
				}

				$model->bills = $bill_view_models;
				$model->success = true;
			}
            catch(Exception $e) {
			    //TODO: Error-specific friendly messages
                $model->friendlyMessage = 'Sorry, but an error has occurred. Please contact support.';
			    $model->errorMessage = $e;
            }

            return $model;
		}

		public function GetCreateModel($req) {
			$model = new BillFormModel();

		    $acctRepo = new Repos\AccountRepo();
		    $contactsRepo = new Repos\ContactRepo();
		    $employeeRepo = new Repos\EmployeeRepo();
			$interlinersRepo = new Repos\InterlinerRepo();
			$paymentRepo = new Repos\PaymentRepo();
			$selectionsRepo = new Repos\SelectionsRepo();

		    $model->accounts = $acctRepo->ListAllForBillsPage();
		    $model->employees = $employeeRepo->ListAllDrivers();
		    foreach ($model->employees as $employee)
				$employee->contact = $contactsRepo->GetById($employee->contact_id);

		    $model->interliners = $interlinersRepo->GetInterlinersList();
		    $model->bill = new \App\Bill();

			$model->packages = array(['packageId' => 0, 'packageCount' => 1, 'packageWeight' => '', 'packageLength' => '', 'packageWidth' => '', 'packageHeight' => '']);

			$model->pickupAddress = new \App\Address();
			$model->deliveryAddress = new \App\Address();
		    $model->charge_selection_submission = null;
			$model->bill->time_call_received = date("U");
			$model->skip_invoicing = false;
			$model->repeat_intervals = $selectionsRepo->GetSelectionsByType('repeat_interval');

			$model->payment_types = $paymentRepo->GetPaymentTypes();
			$model->read_only = false;

			//set minimum and maximum pickup times based on config settings
			$model = $this->setBusinessHours($model);
			//ADMIN status and RATESHEET ID to be dynamically decided
			$model->admin = true;
			$model->ratesheet_id = 1;

			return $model;
		}

		public function GetEditModel($req, $bill_id) {
			$model = new BillFormModel();

			$acctRepo = new Repos\AccountRepo();
			$addrRepo = new Repos\AddressRepo();
			$employeeRepo = new Repos\EmployeeRepo();
			$interlinersRepo = new Repos\InterlinerRepo();
			$billRepo = new Repos\BillRepo();
			$selectionsRepo = new Repos\SelectionsRepo();
			$contactsRepo = new Repos\ContactRepo();
			$paymentRepo = new Repos\PaymentRepo();
			$chargebackRepo = new Repos\ChargebackRepo();
			$activityLogRepo = new Repos\ActivityLogRepo();

			$model->bill = $billRepo->GetById($bill_id);
			if($model->bill === null)
				throw new \Exception('Invalid ID: Unable to find the bill requested', 404);
		    $model->employees = $employeeRepo->ListAllDrivers();
		    foreach ($model->employees as $employee)
				$employee->contact = $contactsRepo->GetById($employee->contact_id);

			$model = $this->setBusinessHours($model);

            $model->pickup_address = $addrRepo->GetById($model->bill->pickup_address_id);
            $model->delivery_address = $addrRepo->GetById($model->bill->delivery_address_id);
			$model->bill->pickup_driver_commission *= 100;
			$model->bill->delivery_driver_commission *= 100;
			$model->bill->percentage_complete *= 100;
			$model->bill->packages = json_decode($model->bill->packages);
			$model->chargeback = $model->bill->chargeback_id === null ? null : $chargebackRepo->GetById($model->bill->chargeback_id);
			$model->payment = $model->bill->payment_id == null ? null : $paymentRepo->GetById($model->bill->payment_id);
			$model->read_only = $billRepo->IsReadOnly($model->bill->bill_id);
			$model->repeat_intervals = $selectionsRepo->GetSelectionsByType('repeat_interval');

			$model->delivery_types = $selectionsRepo->GetSelectionsByType('delivery_type');

			$model->accounts = $acctRepo->ListAllForBillsPage();
			$model->interliners = $interlinersRepo->GetInterlinersList();

			$model->activity_log = $activityLogRepo->GetBillActivityLog($model->bill->bill_id);
			foreach($model->activity_log as $key => $log)
				$model->activity_log[$key]->properties = json_decode($log->properties);

			$model->admin = true;
			$model->ratesheet_id = 1;
			$model->payment_types = $paymentRepo->GetPaymentTypes();

			return $model;
		}

		private function setBusinessHours($model) {
			//set minimum and maximum pickup times based on config settings
			$business_hours_open = explode(':', config('ffe_config.business_hours_open'));
			$business_hours_close = explode(':', config('ffe_config.business_hours_close'));
			$model->time_min = new \DateTime();
			$model->time_min->setTime((int)$business_hours_open[0], (int)$business_hours_open[1]);
			$model->time_max = new \DateTime();
			$model->time_max->setTime((int)$business_hours_close[0], (int)$business_hours_close[1]);

			return $model;
		}
	}
?>
