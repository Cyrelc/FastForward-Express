<?php

namespace App\Http\Models\Bill;

use App\Http\Models;
use App\Http\Repos;
use App\Http\Models\Bill;

class BillModelFactory{
	public function BuildTable($req) {
		$model = new BillsModel();

		$billRepo = new Repos\BillRepo();
		$chargeRepo = new Repos\ChargeRepo();

		$bills = $billRepo->ListAll($req);
		if(count($bills) > 5000)
			abort(413, 'Maximum result limit exceeded. Please limit your search and try again');

		$model = [];
		foreach($bills as $bill) {
			$charges = $chargeRepo->GetByBillId($bill->bill_id);
			$bill->charges = $charges ? $charges : null;
			$model[] = $bill;
		}

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
			$model->accounts = $accountRepo->ListForBillsPage($req->user());
			$model->employees = $employeeRepo->GetDriverList();
			$model->interliners = $interlinerRepo->GetInterlinersList();
			$model->charge_types = $paymentRepo->GetPaymentTypes();
			$model->repeat_intervals = $selectionsRepo->GetSelectionsByType('repeat_interval');

			foreach ($model->employees as $employee)
				$employee->contact = $contactRepo->GetById($employee->contact_id);
		// Possible edge case - if user can create bills for children but not for own account
		} else if($req->user()->can('bills.create.basic.my')) {
			$model->accounts = $accountRepo->ListForBillsPage($req->user(), $req->user()->can('bills.create.basic.children'));
			$model->charge_types = [$paymentRepo->GetAccountPaymentType()];
		}

		$model->bill = new \App\Bill();
		$model->packages = array(['count' => 1, 'weight' => '', 'length' => '', 'width' => '', 'height' => '']);
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

	public function GetEditModel($req, $billId, $permissions) {
		$model = new BillFormModel();

		$accountRepo = new Repos\AccountRepo();
		$activityLogRepo = new Repos\ActivityLogRepo();
		$addressRepo = new Repos\AddressRepo();
		$billRepo = new Repos\BillRepo();
		$chargeRepo = new Repos\ChargeRepo();
		$interlinerRepo = new Repos\InterlinerRepo();
		$lineItemRepo = new Repos\LineItemRepo();
		$paymentRepo = new Repos\PaymentRepo();
		$ratesheetRepo = new Repos\RatesheetRepo();
		$selectionsRepo = new Repos\SelectionsRepo();

		$model->permissions = $permissions;
		$model->bill = $billRepo->GetById($billId, $model->permissions);
		if($model->bill === null)
			abort(404, 'Invalid ID: Unable to find the bill requested');
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
			$model->repeat_intervals = $selectionsRepo->GetSelectionsByType('repeat_interval');
			$charges = $chargeRepo->GetForBill($model->bill->bill_id, null);
			$model->charges = $charges;
		} else
			$model->charges = $chargeRepo->GetForBill($model->bill->bill_id);
		foreach($model->charges as $key => $charge)
			$model->charges[$key]->lineItems = $lineItemRepo->GetByChargeId($charge->charge_id);

		$model->accounts = $accountRepo->ListForBillsPage($req->user(), $req->user()->can('bills.create.basic.children'));

		$model->ratesheets = $ratesheetRepo->GetForBillsPage();
		$model->charge_types = $paymentRepo->GetPaymentTypes();
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
		$timeMin = new \DateTime();
		$timeMax = new \DateTime();
		$timeMin->setTime((int)$business_hours_open[0], (int)$business_hours_open[1]);
		$timeMax->setTime((int)$business_hours_close[0], (int)$business_hours_close[1]);
		$model->time_min = $timeMin->format(\DateTime::ATOM);
		$model->time_max = $timeMax->format(\DateTime::ATOM);

		return $model;
	}
}
?>
