<?php
namespace App\Http\Validation;

use App\Http\Repos;
use Illuminate\Validation\Rule;

class BillValidationRules {
	public function GetValidationRules($req, $oldBill, $permissions) {
		$accountRepo = new Repos\AccountRepo();
		$partialsRules = new PartialsValidationRules();

		$rules = [
			'delivery_type' => 'required',
			'is_min_weight_size' => 'required',
			'is_pallet' => 'required',
			'pickup_account_id' => '',
			'time_pickup_scheduled' => 'required|date',
			'time_delivery_scheduled' => 'required|date|after_or_equal:time_pickup_scheduled',
			'updated_at' => 'exclude_if:bill_id,null|date|date_equals:' . ($oldBill ? $oldBill->updated_at : ''),
			'use_imperial' => 'required'
		];

		$messages = [
			'delivery_type.required' => 'Please select a delivery type',
			'time_delivery_scheduled.date' => 'Delivery date is in an incorrect format',
			'time_delivery_scheduled.required' => 'Delivery date is required',
			'time_pickup_scheduled.date' => 'Pickup date is in an incorrect format',
			'time_pickup_scheduled.required' => 'Pickup date is required',
			'updated_at.date_equals' => 'This bill has been modified since you loaded the page. Please re-load the bill and try again'
		];

		if($req->is_min_weight_size === 'false') {
			$rules = array_merge($rules, [
				'packages' => 'required',
				'packages.*.packageCount' => 'required|integer|min:1',
				'packages.*.packageWeight' => 'required|numeric|min:1',
				'packages.*.packageLength' => 'required|numeric|min:1',
				'packages.*.packageWidth' => 'required|numeric|min:1',
				'packages.*.packageHeight' => 'required|numeric|min:1'
			]);
			$messages = array_merge($messages, []);
		}
		
		$pickupAddress = $partialsRules->GetAddressMinValidationRules($req, 'pickup_address', 'Pickup');
		$rules = array_merge($rules, $pickupAddress['rules']);
		$messages = array_merge($messages, $pickupAddress['messages']);

		$deliveryAddress = $partialsRules->GetAddressMinValidationRules($req, 'delivery_address', 'Delivery');
		$rules = array_merge($rules, $deliveryAddress['rules']);
		$messages = array_merge($messages, $deliveryAddress['messages']);

		$chargeAccount = $req->charge_account_id ? $accountRepo->GetById($req->charge_account_id) : null;
		if($chargeAccount && $chargeAccount->custom_field && $chargeAccount->is_custom_field_mandatory)
			$rules = array_merge($rules, ['charge_reference_value' => 'required']);
		$deliveryAccount = $req->delivery_account_id ? $accountRepo->GetById($req->delivery_account_id) : null;
		if($deliveryAccount && $deliveryAccount->custom_field && $deliveryAccount->is_custom_field_mandatory)
			$rules = array_merge($rules, ['delivery_reference_value' => 'required']);
		$pickupAccount = $req->pickup_account_id ? $accountRepo->GetById($req->pickup_account_id) : null;
		if($pickupAccount && $pickupAccount->custom_field && $pickupAccount->is_custom_field_mandatory)
			$rules = array_merge($rules, ['pickup_reference_value' => 'required']);

		if($req->user()->accountUser) {
			$basic = $this->getBasicValidationRulesAccountUser($req);
			$rules = array_merge($rules, $basic['rules']);
			$messages = array_merge($messages, $basic['messages']);
		} else {
			$basic = $this->getBasicValidationRulesEmployee($req);
			$rules = array_merge($rules, $basic['rules']);
			$messages = array_merge($messages, $basic['messages']);
		}

		if(filter_var($req->bill_id, FILTER_VALIDATE_BOOLEAN) ? $permissions['updateDispatch'] : $permissions['createFull']) {
			$dispatch = $this->getDispatchValidationRules($req);
			$rules = array_merge($rules, $dispatch['rules']);
			$messages = array_merge($messages, $dispatch['messages']);
		}

		if(filter_var($req->bill_id, FILTER_VALIDATE_BOOLEAN) ? $permissions['updateBilling'] : $permissions['createFull']) {
			$billing = $this->getDispatchValidationRules($req);
			$rules = array_merge($rules, $billing['rules']);
			$messages = array_merge($messages, $billing['messages']);
		}

		return ['rules' => $rules, 'messages' => $messages];
	}

	/**
	 * Private functions
	 */

	private function getBasicValidationRulesAccountUser($req) {
		$accountRepo = new Repos\AccountRepo();
		$paymentRepo = new Repos\PaymentRepo();
		$accountPaymentTypeId = $paymentRepo->GetAccountPaymentType()->payment_type_id;

		$validAccounts = $accountRepo->ListForBillsPage($accountRepo->GetMyAccountIds($req->user(), $req->user()->can('bills.create.basic.children')));
		$validAccountIds = [];

		foreach($validAccounts as $validAccount)
			array_push($validAccountIds, $validAccount->account_id);

		$businessHoursOpen = explode(':', config('ffe_config.business_hours_open'));
		$businessHoursClose = explode(':', config('ffe_config.business_hours_close'));
		if(date('w') === 6 || date('w') === 0)
			$minDateTime = strtotime('next monday');
		else
			$minDateTime = strtotime('today');
		$minDateTime->setTime((int)$businessHoursOpen[0], (int)$businessHoursOpen[1]);
		activity('system_debug')->log('checking against minimum datetime of: ' . $minDateTime);
		$rules = [
			'charges.0.chargeId' => ['required', Rule::in($validAccountIds)],
			'delivery_account_id' => ['exclude_unless:delivery_address_type,Account|required', Rule::in($validAccountIds)],
			'charges.0.chargeType.payment_type_id' => ['required', Rule::in([$accountPaymentTypeId])],
			'pickup_account_id' => ['exclude_unless:pickup_address_type,Account|required', Rule::in($validAccountIds)],
			'time_pickup_expected' => ['required|date|after:' . $minDateTime],
			'time_delivery_expected' => ['required|data|after:time_pickup_expected']
		];

		$messages = [
			'delivery_account_id.required' => 'Delivery Account is required when address input type is Account',
			'pickup_account_id.required' => 'Pickup Account is required when address input type is Account'
		];

		return ['rules' => $rules, 'messages' => $messages];
	}

	private function getBasicValidationRulesEmployee($req) {
		$rules = [
			'delivery_account_id' => 'exclude_unless:delivery_address_type,Account|required|exists:accounts,account_id',
			// 'payment_type.name' => 'required|exists:payment_types,name',
			'pickup_account_id' => 'exclude_unless:pickup_address_type,Account|required|exists:accounts,account_id'
		];

		$messages = [

		];

		return ['rules' => $rules, 'messages' => $messages];
	}

	private function getDispatchValidationRules($req) {
		$rules = [
			'time_dispatched' => 'date',
			'time_picked_up' => 'date',
			'time_delivered' => 'date',
		];

		$messages = [

		];

		return ['rules' => $rules, 'messages' => $messages];
	}

	private function getBillingValidationRules($req) {
		$paymentRepo = new Repos\PaymentRepo();

		$rules = [
			'bill_number' => 'sometimes|unique:bills,bill_number,' . $req->bill_id . ',bill_id',
			'skip_invoicing' => 'required',
		];

		$messages = [

		];

		if($req->interliner_id != "") {
			$rules = array_merge($rules, ['interliner_id' => 'required', 'interliner_reference_value' => 'alpha_dash|min:4', 'interliner_cost' => "min:0", 'interliner_cost_to_customer' => 'min:0']);
		}

		$rules = array_merge($rules, ['charges.*.paymentType.payment_type_id' => 'required|exists:payment_types,payment_type_id']);

		foreach($req->charges as $key => $charge) {
			if($charge['chargeType']['payment_type_id'] === $paymentRepo->GetAccountPaymentType()) {
				$rules = array_merge($rules, [
					'charge.' . $key . '.chargeId' => 'required|exists:accounts,account_id']
				);
			}
			else if($charge['chargeType']['payment_type_id'] === $paymentRepo->GetPaymentTypeByName('Employee')) {
				$rules = array_merge($rules, [
					'charge.' . $key . '.chargeId' => 'required|exists:employees,employee_id'
					]);
			}
			else {
				$rules = array_merge($rules, [
					'charge.' . $key . '.chargeId' => 'required|exists:payment_types,payment_type_id']
				);
			}
		}
		// if($req->payment_type == 'Account') {
		// 	$rules = array_merge($rules, ['charge_account_id' => 'required']);
		// 	$messages = array_merge($messages, ['charge_account_id.required' => 'Charge Account ID is required', 'charge_account_reference_value.required' => 'Charge Account requires a reference value']);
		// } else if ($req->payment_type == 'Employee') {
		// 	$rules = array_merge($rules, ['charge_employee_id' => 'required']);
		// 	$messages = array_merge($messages, ['charge_employee_id.required' => 'Must select an employee to charge back to']);
		// }

		return ['rules' => $rules, 'messages' => $messages];
	}
}

