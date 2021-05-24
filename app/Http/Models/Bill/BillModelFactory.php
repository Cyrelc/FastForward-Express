<?php

namespace App\Http\Models\Bill;

use App\Http\Models;
use App\Http\Repos;
use App\Http\Models\Bill;

class BillModelFactory{
	public function ListAll() {
		$model = new BillsModel();

		$billRepo = new Repos\BillRepo();
		$accountRepo = new Repos\AccountRepo();
		$employeeRepo = new Repos\EmployeeRepo();
		$contactRepo = new Repos\ContactRepo();

		$bills = $billRepo->ListAll(null);

		$bill_view_models = Array();

		foreach ($bills as $bill){
			$bill_view_model = new BillViewModel();

			$bill_view_model->bill = $bill;
			$bill_view_model->account = $accountRepo->GetById($bill->charge_account_id);
			if ($bill_view_model->account === null) {
				$bill_view_model->account = new \App\Account();
				$bill_view_model->account->name = "Cash";
			}

			$bill_view_model->pickup_driver = $employeeRepo->GetById($bill->pickup_driver_id);
			$pickup_driver_contact = $contactRepo->GetById($bill_view_model->pickup_driver->contact_id);
			$bill_view_model->pickup_driver_name = $pickup_driver_contact->first_name . ' ' . $pickup_driver_contact->last_name;

			$bill_view_model->delivery_driver = $employeeRepo->GetById($bill->delivery_driver_id);
			$delivery_driver_contact = $contactRepo->GetById($bill_view_model->delivery_driver->contact_id);
			$bill_view_model->delivery_driver_name = $delivery_driver_contact->first_name . ' ' . $delivery_driver_contact->last_name;

			array_push($bill_view_models, $bill_view_model);
		}

		$model->bills = $bill_view_models;
		$model->success = true;

		return $model;
	}

	public function GetCreateModel($req, $permissions) {
		$model = new BillFormModel();

		$model->permissions = $permissions;

		$accountRepo = new Repos\AccountRepo();
		$contactRepo = new Repos\ContactRepo();
		$employeeRepo = new Repos\EmployeeRepo();
		$interlinerRepo = new Repos\InterlinerRepo();
		$paymentRepo = new Repos\PaymentRepo();
		$ratesheetRepo = new Repos\RatesheetRepo();
		$selectionsRepo = new Repos\SelectionsRepo();

		if($permissions['createFull']) {
			$model->accounts = $accountRepo->ListForBillsPage(null);
			$model->employees = $employeeRepo->GetDriverList();
			$model->interliners = $interlinerRepo->GetInterlinersList();
			$model->payment_types = $paymentRepo->GetPaymentTypes();
			$model->repeat_intervals = $selectionsRepo->GetSelectionsByType('repeat_interval');

			foreach ($model->employees as $employee)
				$employee->contact = $contactRepo->GetById($employee->contact_id);
		// Possible edge case - if user can create bills for children but not for own account
		} else if($req->user()->can('bills.create.basic.my')) {
			$model->accounts = $accountRepo->ListForBillsPage($accountRepo->GetMyAccountIds($req->user(), $req->user()->can('bills.create.basic.children')));
			$model->payment_types = [$paymentRepo->GetAccountPaymentType()];
		}

		$model->bill = new \App\Bill();
		$model->packages = array(['packageId' => 0, 'packageCount' => 1, 'packageWeight' => '', 'packageLength' => '', 'packageWidth' => '', 'packageHeight' => '']);
		$model->pickupAddress = new \App\Address();
		$model->ratesheets = $ratesheetRepo->GetForBillsPage();
		$model->deliveryAddress = new \App\Address();

		$model->charge_selection_submission = null;
		$model->bill->time_call_received = date("U");
		$model->skip_invoicing = false;

		//set minimum and maximum pickup times based on config settings
		$model = $this->setBusinessHours($model);
		//ADMIN status and RATESHEET ID to be dynamically decided
		$model->ratesheet_id = 1;

		return $model;
	}

	public function GetEditModel($billId, $permissions) {
		$model = new BillFormModel();

		$accountRepo = new Repos\AccountRepo();
		$activityLogRepo = new Repos\ActivityLogRepo();
		$addressRepo = new Repos\AddressRepo();
		$billRepo = new Repos\BillRepo();
		$chargebackRepo = new Repos\ChargebackRepo();
		$contactRepo = new Repos\ContactRepo();
		$employeeRepo = new Repos\EmployeeRepo();
		$interlinerRepo = new Repos\InterlinerRepo();
		$paymentRepo = new Repos\PaymentRepo();
		$ratesheetRepo = new Repos\RatesheetRepo();
		$selectionsRepo = new Repos\SelectionsRepo();

		$model->bill = $billRepo->GetById($billId, $permissions);
		$model->permissions = $permissions;
		if($model->bill === null)
			throw new \Exception('Invalid ID: Unable to find the bill requested', 404);
		if($permissions['viewActivityLog']) {
			$model->activity_log = $activityLogRepo->GetBillActivityLog($model->bill->bill_id);
			foreach($model->activity_log as $key => $log) {
				$model->activity_log[$key]->properties = json_decode($log->properties);
			}
		}

		if($permissions['viewDispatch']) {
			$model->bill->pickup_driver_commission *= 100;
			$model->bill->delivery_driver_commission *= 100;
			$model->interliners = $interlinerRepo->GetInterlinersList();
		}

		if($permissions['viewBilling']) {
			$model->chargeback = $model->bill->chargeback_id === null ? null : $chargebackRepo->GetById($model->bill->chargeback_id);
			$model->repeat_intervals = $selectionsRepo->GetSelectionsByType('repeat_interval');
			$model->payment = $model->bill->payment_id == null ? null : $paymentRepo->GetById($model->bill->payment_id);
		}

		$model->accounts = $accountRepo->ListForBillsPage(null);

		$model->ratesheets = $ratesheetRepo->GetForBillsPage();
		$model->payment_types = $paymentRepo->GetPaymentTypes();
		$model->read_only = $billRepo->IsReadOnly($model->bill->bill_id);
		$model = $this->setBusinessHours($model);

		$model->pickup_address = $addressRepo->GetById($model->bill->pickup_address_id);
		$model->delivery_address = $addressRepo->GetById($model->bill->delivery_address_id);
		$model->bill->packages = json_decode($model->bill->packages);
		$model->delivery_types = $selectionsRepo->GetSelectionsByType('delivery_type');

		$model->ratesheet_id = 1;

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
