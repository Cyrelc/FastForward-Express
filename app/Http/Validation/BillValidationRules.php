<?php
namespace App\Http\Validation;

use App\Http\Repos;
use App\Rules\AlphaNumDashUnderscoreSpace;
use Illuminate\Validation\Rule;

class BillValidationRules {
	// public function GetMobileValidationRules($req, $oldBill, $permissions) {
	// 	$rules = [
	// 		'time_ten_foured' => 'date',
	// 		'time_delivered' => 'date',
	// 		'time_picked_up' => 'date',
	// 	];

	// 	return ['rules' => $rules, 'messages' => []];
	// }

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

		if(!filter_var($req->is_min_weight_size, FILTER_VALIDATE_BOOLEAN)) {
			$rules = array_merge($rules, ['packages' => 'required|array']);
			$messages = array_merge($messages, ['packages.required' => 'Please enter weight and dimension information for a minimum of 1 package']);
			foreach($req->packages as $key => $package) {
				$rules = array_merge($rules, [
					'packages.' . $key . '.count' => 'required|integer|min:1',
					'packages.' . $key . '.weight' => 'required|numeric|min:1',
					'packages.' . $key . '.length' => 'required|numeric|min:1',
					'packages.' . $key . '.width' => 'required|numeric|min:1',
					'packages.' . $key . '.height' => 'required|numeric|min:1'
				]);
				$messages = array_merge($messages, [
					'packages.' . $key . '.count.required' => 'Please enter a count for package at row ' . $key,
					'packages.' . $key . '.weight.required' => 'Please enter a weight for package at row ' . $key,
					'packages.' . $key . '.length.required' => 'Please enter a length for package at row ' . $key,
					'packages.' . $key . '.width.required' => 'Please enter a width for a package at row ' . $key,
					'packages.' . $key . '.height.required' => 'Please enter a height for a package at row ' . $key
				]);
			}
		}
		
		$pickupAddress = $partialsRules->GetAddressMinValidationRules($req, 'pickup_address', 'Pickup');
		$rules = array_merge($rules, $pickupAddress['rules']);
		$messages = array_merge($messages, $pickupAddress['messages']);

		$deliveryAddress = $partialsRules->GetAddressMinValidationRules($req, 'delivery_address', 'Delivery');
		$rules = array_merge($rules, $deliveryAddress['rules']);
		$messages = array_merge($messages, $deliveryAddress['messages']);

		// Handle account reference values
		$deliveryAccount = $req->delivery_account_id ? $accountRepo->GetById($req->delivery_account_id) : null;
		if($deliveryAccount && $deliveryAccount->custom_field && $deliveryAccount->is_custom_field_mandatory)
			$rules = array_merge($rules, ['delivery_reference_value' => ['required', new AlphaNumDashUnderscoreSpace]]);
		$pickupAccount = $req->pickup_account_id ? $accountRepo->GetById($req->pickup_account_id) : null;
		if($pickupAccount && $pickupAccount->custom_field && $pickupAccount->is_custom_field_mandatory)
			$rules = array_merge($rules, ['pickup_reference_value' => ['required', new AlphaNumDashUnderscoreSpace]]);

		if($req->user()->employee || $req->user()->hasRole('superAdmin')) {
			$basic = $this->getBasicValidationRulesEmployee($req);
			$rules = array_merge($rules, $basic['rules']);
			$messages = array_merge($messages, $basic['messages']);
		} else {
			$basic = $this->getBasicValidationRulesAccountUser($req);
			$rules = array_merge($rules, $basic['rules']);
			$messages = array_merge($messages, $basic['messages']);
		}

		if($req->bill_id != null ? ($permissions['editDispatch'] || $permissions['editDispatchMy']) : $permissions['createFull']) {
			$dispatch = $this->getDispatchValidationRules($req);
			$rules = array_merge($rules, $dispatch['rules']);
			$messages = array_merge($messages, $dispatch['messages']);
		}

		if($req->bill_id != null ? $permissions['editBilling'] : $permissions['createFull']) {
			$billing = $this->getBillingValidationRules($req);
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
		$accountPaymentTypeId = (int)$paymentRepo->GetAccountPaymentType()->payment_type_id;

		$chargeAccount = $accountRepo->GetById($req->charge_account_id);
		$deliveryAccount = $accountRepo->GetById($req->delivery_account_id);
		$pickupAccount = $accountRepo->GetById($req->pickup_account_id);

		$validAccounts = $accountRepo->ListForBillsPage($req->user(), $req->user()->can('bills.create.basic.children'));
		$validAccountIds = [];

		foreach($validAccounts as $validAccount)
			array_push($validAccountIds, $validAccount->account_id);

		$businessHoursOpen = explode(':', config('ffe_config.business_hours_open'));
		$businessHoursClose = explode(':', config('ffe_config.business_hours_close'));
		if(date('w') === 6 || date('w') === 0)
			$minDateTime = new \DateTime('next monday');
		else
			$minDateTime = new \DateTime('today');

		$minDateTime->setTime((int)$businessHoursOpen[0], (int)$businessHoursOpen[1]);
		$currentDateTime = new \DateTime();
		if($currentDateTime > $minDateTime)
			$minDateTime = $currentDateTime;
		$minDateTime = $minDateTime->format('Y-m-d H:i:s');

		// Server-authoritative floor: the scheduled delivery must be at least the selected delivery
		// type's computed minimum after pickup. The minimum is recomputed server-side from the
		// account's ratesheet and the route, so a tampered client value cannot buy a faster service.
		$minimumDeliveryGapRule = function($attribute, $value, $fail) use ($req) {
			$chargeAccount = (new Repos\AccountRepo())->GetById($req->charge_account_id);
			$ratesheetId = $chargeAccount->ratesheet_id ?? null;
			if(!$ratesheetId)
				return;

			$ratesheet = (new Repos\RatesheetRepo())->GetById($ratesheetId);
			$deliveryTypeId = is_array($req->delivery_type) ? ($req->delivery_type['id'] ?? null) : null;
			if(!$ratesheet || $deliveryTypeId === null)
				return;

			$minimumHours = (new \App\Http\Models\Bill\ChargeModelFactory())->GetComputedDeliveryTime(
				$ratesheet,
				$req->pickup_address_lat, $req->pickup_address_lng,
				$req->delivery_address_lat, $req->delivery_address_lng,
				$deliveryTypeId
			);
			if($minimumHours === null)
				return; // route can't be auto-resolved — leave it for staff to price/schedule

			try {
				$pickup = new \DateTime($req->time_pickup_scheduled);
				$delivery = new \DateTime($value);
			} catch(\Throwable $e) {
				return;
			}

			$earliest = (clone $pickup)->modify('+' . (int) round($minimumHours * 60) . ' minutes');
			if($delivery < $earliest) {
				$readableHours = rtrim(rtrim(number_format($minimumHours, 2), '0'), '.');
				$fail('The selected delivery type needs at least ' . $readableHours . ' hour(s) after pickup. Please choose a later delivery time or a faster delivery type.');
			}
		};

		// A ratesheet can refuse a booking outright ("exceeds trailer capacity - please call us"). The
		// quote screen already shows the message, but nothing stopped a customer submitting anyway:
		// BillController::store takes its line items from the request and never re-prices. This is the
		// backstop, and it lives here - in the ACCOUNT USER rules - on purpose. Employees get the
		// relaxed rule set, so a refusal stays advisory for them: to staff it reads as "cannot be
		// auto-priced", and they must still be able to book it and price it by hand.
		$refusalRule = function($attribute, $value, $fail) use ($req) {
			$chargeAccount = (new Repos\AccountRepo())->GetById($req->charge_account_id);
			$ratesheetId = $chargeAccount->ratesheet_id ?? null;
			if(!$ratesheetId)
				return;

			$ratesheet = (new Repos\RatesheetRepo())->GetById($ratesheetId);
			if(!$ratesheet)
				return;

			$reason = (new \App\Http\Models\Bill\ChargeModelFactory())->GetRefusalReason($ratesheet, $req);
			if($reason)
				$fail($reason);
		};

		$rules = [
			'accept_terms_and_conditions' => 'required|accepted',
			'charge_account_id' => 'exclude_unless:charge_type.payment_type_id,' . $accountPaymentTypeId . '|required',
			'charge_type.payment_type_id' => ['required', 'integer', Rule::in([$accountPaymentTypeId])],
			'delivery_account_id' => ['exclude_unless:delivery_address_type,Account', 'required', 'integer', Rule::in($validAccountIds)],
			'delivery_type' => ['required', $refusalRule],
			'pickup_account_id' => ['exclude_unless:pickup_address_type,Account', 'required', 'integer', Rule::in($validAccountIds)],
			'time_delivery_scheduled' => ['required', 'date', 'after:time_pickup_scheduled', $minimumDeliveryGapRule],
			'time_pickup_scheduled' => ['required', 'date', 'after:' . $minDateTime],
		];

		$messages = [
			'accept_terms_and_conditions.accepted' => 'Please accept the terms and conditions',
			'charge_account_id.required' => 'Please select a valid account to charge the bill to',
			'charge_type.payment_type_id.in' => 'Selected payment method appears to be invalid',
			'charge_type.payment_type_id.required' => 'Please select a payment method',
			'delivery_account_id.required' => 'Delivery Account is required when address input type is Account',
			'pickup_account_id.required' => 'Pickup Account is required when address input type is Account',
			'time_pickup_scheduled.after' => 'Pickup time cannot be in the past'
		];

		if($chargeAccount && $chargeAccount->is_custom_field_mandatory) {
			$rules = array_merge($rules, ['charge_reference_value' => 'required']);
			$messages = array_merge($messages, ['charge_reference_value.required' => $chargeAccount->custom_field . ' can not be empty']);
		}

		if($pickupAccount && $pickupAccount->is_custom_field_mandatory) {
			$rules = array_merge($rules, ['pickup_reference_value' => 'required']);
			$messages = array_merge($messages, ['pickup_reference_value.required' => $pickupAccount->custom_field . ' can not be empty']);
		}

		if($deliveryAccount && $deliveryAccount->is_custom_field_mandatory) {
			$rules = array_merge($rules, ['delivery_reference_value' => 'required']);
			$messages = array_merge($messages, ['delivery_reference_value.required' => $deliveryAccount->custom_field . ' can not be empty']);
		}

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
			'time_dispatched' => 'nullable|date',
			'time_picked_up' => 'nullable|date',
			'time_delivered' => 'nullable|date',
			'time_ten_foured' => 'nullable|date'
		];

		$messages = [

		];

		return ['rules' => $rules, 'messages' => $messages];
	}

	private function getBillingValidationRules($req) {
		$accountRepo = new Repos\AccountRepo();
		$paymentRepo = new Repos\PaymentRepo();

		$rules = [
			'bill_number' => 'nullable|sometimes|unique:bills,bill_number,' . $req->bill_id . ',bill_id',
			'skip_invoicing' => 'required',
		];

		$messages = [

		];

		if($req->interliner_id != "") {
			$rules = array_merge($rules, ['interliner_id' => 'required', 'interliner_reference_value' => 'alpha_dash|min:4', 'interliner_cost' => "min:0", 'interliner_cost_to_customer' => 'min:0']);
		}

		$rules = array_merge($rules, ['charges.*.chargeType.payment_type_id' => 'required|exists:payment_types,payment_type_id']);

		if($req->charges)
			foreach($req->charges as $key => $charge) {
				if($charge['chargeType']['payment_type_id'] == $paymentRepo->GetAccountPaymentType()->payment_type_id) {
					$rules = array_merge($rules, [
						'charges.' . $key . '.charge_account_id' => 'required|exists:accounts,account_id']
					);
					$messages = array_merge($rules, [
						'charges.' . $key . '.charge_account_id.required' => 'Account ID for requested charge is missing. Please try again',
						'charges.' . $key . 'charge_account_id.exists' => 'Account ID ' . $charge['charge_account_id'] . 'does not exist. Please try again' 
					]);
					$account = $accountRepo->GetById($charge['charge_account_id']);
					if($account->custom_field && $account->is_custom_field_mandatory) {
						$rules = array_merge($rules, [
							'charges.' . $key . '.charge_reference_value' => ['required', new AlphaNumDashUnderscoreSpace]
						]);
						$messages = array_merge($messages, [
							'charges.' . $key . '.charge_reference_value.required' => $account->custom_field . ' is required',
							'charges.' . $key . '.charge_reference_value.AlphaNumDashUnderscoreSpace' => $account->custom_field . ' can only contain alpha numeric characters, dashes, or underscores'
						]);
					}
				} else if($charge['chargeType']['payment_type_id'] === $paymentRepo->GetPaymentTypeByName('Employee')) {
					$rules = array_merge($rules, [
						'charges.' . $key . '.chargeId' => 'required|exists:employees,employee_id'
						]);
				} else {
					$rules = array_merge($rules, [
						'charges.' . $key . '.chargeType.payment_type_id' => 'required|exists:payment_types,payment_type_id']
					);
					$messages = array_merge($messages, [
						'charges.' . $key . '.chargeType.payment_type_id.required' => $charge['chargeType']['name'] . ' payment type id incorrect. Please contact support',
						'charges.' . $key . '.chargeType.payment_type_id.exists' => $charge['chargeType']['name'] . ' payment type id incorrect. Please contact support'
					]);
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

