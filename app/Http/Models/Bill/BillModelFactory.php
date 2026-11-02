<?php

namespace App\Http\Models\Bill;

use App\Models\Address;
use App\Models\Bill;
use App\Http\Models;
use App\Http\Repos;
use App\Models\Contact;

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
		$billRepo = new Repos\BillRepo();
		$employeeRepo = new Repos\EmployeeRepo();
		$interlinerRepo = new Repos\InterlinerRepo();
		$paymentRepo = new Repos\PaymentRepo();
		$ratesheetRepo = new Repos\RatesheetRepo();
		$selectionsRepo = new Repos\SelectionsRepo();

		if($permissions['createFull']) {
			$model->accounts = $accountRepo->ListForBillsPage($req->user(), false, true);
			$model->interliners = $interlinerRepo->GetInterlinersList();
			$model->charge_types = $paymentRepo->GetPaymentTypes();
			$model->repeat_intervals = $selectionsRepo->GetSelectionsByType('repeat_interval');
		// Possible edge case - if user can create bills for children but not for own account
		} else if($req->user()->can('bills.create.basic.my')) {
			$model->accounts = $accountRepo->ListForBillsPage($req->user(), $req->user()->can('bills.create.basic.children'), true);
			$model->charge_types = [$paymentRepo->GetAccountPaymentType()];
		}

		$model->bill = new Bill();
		$model->packages = array(['count' => 1, 'weight' => '', 'length' => '', 'width' => '', 'height' => '']);
		$model->pickup_address = new Address();
		$model->ratesheets = $ratesheetRepo->GetForBillsPage();
		$model->delivery_address = new Address();

		$model->charge_selection_submission = null;
		$model->bill->time_call_received = date("U");
		$model->skip_invoicing = false;

		//set minimum and maximum pickup times based on config settings
		$model = $this->setBusinessHours($model);
		//ADMIN status and RATESHEET ID to be dynamically decided
		$model->ratesheet_id = 1;

		return $model;
	}

	public function GetCopyModel($req, $permissions) {
		$model = $this->GetCreateModel($req, $permissions);

		$addressRepo = new Repos\AddressRepo();
		$billRepo = new Repos\BillRepo();
		$chargeRepo = new Repos\ChargeRepo();
		$lineItemRepo = new Repos\LineItemRepo();

		$template = $billRepo->GetById($req->copy_from);

		$model->pickup_address = $addressRepo->GetById($template->pickup_address_id);
		$model->pickup_address->address_id = null;
		$model->delivery_address = $addressRepo->GetById($template->delivery_address_id);
		$model->delivery_address->address_id = null;
		$model->bill->pickup_account_id = $template->pickup_account_id;
		$model->bill->delivery_account_id = $template->delivery_account_id;
		$model->charges = $chargeRepo->GetByBillId($template->bill_id);

		foreach($model->charges as $key => $charge)
			$model->charges[$key]->lineItems = $lineItemRepo->GetByChargeId($charge->charge_id);

		foreach($model->charges as $chargeKey => $charge) {
			$model->charges[$chargeKey]->charge_id = null;
			foreach($model->charges[$chargeKey]->lineItems as $lineItemKey => $lineItem) {
				$model->charges[$chargeKey]->lineItems[$lineItemKey]->line_item_id = null;
				$model->charges[$chargeKey]->lineItems[$lineItemKey]->invoice_id = null;
				$model->charges[$chargeKey]->lineItems[$lineItemKey]->pickup_manifest_id = null;
				$model->charges[$chargeKey]->lineItems[$lineItemKey]->delivery_manifest_id = null;
				$model->charges[$chargeKey]->lineItems[$lineItemKey]->delivery_driver_id = null;
				$model->charges[$chargeKey]->lineItems[$lineItemKey]->pickup_driver_id = null;
				$model->charges[$chargeKey]->lineItems[$lineItemKey]->amendment_number = null;
			}
		}

		return $model;
	}

	public function GetEditModel($req, $billId, $permissions) {
		$model = new BillFormModel();

		$accountRepo = new Repos\AccountRepo();
		$activityLogRepo = new Repos\ActivityLogRepo();
		$addressRepo = new Repos\AddressRepo();
		$billRepo = new Repos\BillRepo();
		$chargeRepo = new Repos\ChargeRepo();
		$employeeRepo = new Repos\EmployeeRepo();
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
			$charges = $chargeRepo->GetByBillId($model->bill->bill_id, null);
			$model->charges = $charges;
		} else
			$model->charges = $chargeRepo->GetByBillId($model->bill->bill_id);
		foreach($model->charges as $key => $charge)
			$model->charges[$key]->lineItems = $lineItemRepo->GetByChargeId($charge->charge_id);

		$model->accounts = $accountRepo->ListForBillsPage($req->user(), $req->user()->can('bills.create.basic.children'));

		$model->ratesheets = $ratesheetRepo->GetForBillsPage();
		$model->charge_types = $paymentRepo->GetPaymentTypes();
		$model = $this->setBusinessHours($model);

		$model->pickup_address = $model->bill->pickup_address;
		$model->delivery_address = $model->bill->delivery_address;

		$model->bill->packages = json_decode($model->bill->packages);
		if($model->bill->pickup_driver_id)
			$model->bill->pickup_driver_number = $model->bill->pickup_employee->employee_number;
		if($model->bill->delivery_driver_id)
			$model->bill->delivery_driver_number = $model->bill->delivery_employee->employee_number;
		$model->delivery_types = $selectionsRepo->GetSelectionsByType('delivery_type');

		$model->ratesheet_id = 1;

		return $model;
	}

	public function GetPrintAsInvoiceModel($billId, $permissions) {
		$model = new Models\Invoice\InvoiceTable();

		$addressRepo = new Repos\AddressRepo();
		$billRepo = new Repos\BillRepo();
		$chargeRepo = new Repos\ChargeRepo();
		$selectionsRepo = new Repos\SelectionsRepo();

		$bill = $billRepo->GetById($billId, $permissions);
		$charges = $chargeRepo->GetByBillId($billId);
		$deliveryAddress = $addressRepo->GetById($bill->delivery_address_id);
		$pickupAddress = $addressRepo->GetById($bill->pickup_address_id);

		$billCost = 0;
		$unpaid = 0;
		foreach($charges as $charge) {
			foreach($charge->lineItems as $lineItem) {
				$billCost += $lineItem->price;
				if(!filter_var($lineItem->paid, FILTER_VALIDATE_BOOLEAN))
					$unpaid += $lineItem->price;
			}
		}
		$tax = $billCost * config('ffe_config.gst') / 100;

		$model->parent = new \stdClass();
		$model->parent->account_number = 'Bill';
		$model->parent->billing_address = $pickupAddress;
		$model->parent->shipping_address = $deliveryAddress;
		$model->parent->name = $bill->bill_id;
		$model->parent->invoice_comment = $bill->description;

		$model->invoice = new \stdClass();
		$model->invoice->bill_cost = $billCost;
		$model->invoice->bill_count = 1;
		$model->invoice->finalized = $bill->percentage_complete == 100 ? 1 : 0;
		$model->invoice->min_invoice_amount = null;
		$model->invoice->discount = 0;
		$model->invoice->fuel_surcharge = 0;
		$model->invoice->invoice_id = null;
		$model->invoice->bill_end_date = $bill->time_pickup_scheduled;
		$model->invoice->tax = number_format($tax, 2);
		$model->invoice->total_cost = $billCost + $tax;

		$model->tables = [];
		$model->tables[0] = new \stdClass();
		$model->tables[0]->headers = [
			'Date' => 'time_pickup_scheduled',
			'Bill ID' => 'bill_id',
			'Waybill Number' => 'bill_number',
			'Type' => 'delivery_type'
		];

		$deliveryType = $selectionsRepo->GetSelectionByTypeAndValue('delivery_type', $bill->delivery_type);
		$bill->delivery_type = $deliveryType->name;

		$model->tables[0]->bills = [$bill];
		$model->unpaid_invoices = [];
		$model->account_owing = $billCost + $tax;

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
