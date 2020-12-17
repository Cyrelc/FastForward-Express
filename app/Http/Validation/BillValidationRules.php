<?php
namespace App\Http\Validation;

class BillValidationRules {
    public function GetValidationRules($req, $oldBill) {
		$rules = [	'time_pickup_scheduled' => 'required|date',
					'time_delivery_scheduled' => 'required|date',
					'time_call_received' => 'required|date',
					'time_dispatched' => 'date',
					'time_picked_up' => 'date',
					'time_delivered' => 'date',
					'delivery_type' => 'required',
					'payment_type' => 'required',
					'is_min_weight_size' => 'required',
					'is_pallet' => 'required',
					'use_imperial' => 'required',
					'skip_invoicing' => 'required',
					'bill_number' => 'sometimes|unique:bills,bill_number,' . $req->bill_id . ',bill_id'
					// 'charge_reference_value' => 'sometimes|required',
					// 'pickup_reference_value' => 'sometimes|required',
					// 'delivery_reference_value' => 'sometimes|required'
				];

    	$messages = ['time_pickup_scheduled.required' => 'Pickup date is required',
					'time_pickup_scheduled.date' => 'Pickup date is in an incorrect format',
					'time_delivery_scheduled.required' => 'Delivery date is required',
					'time_delivery_scheduled.date' => 'Delivery date is in an incorrect format',
					'time_call_received.required' => 'Call Received Time is required',
					'time_call_received.date' => 'Call received time is in an incorrect format',
					'delivery_type.required' => 'Please select a delivery type',
					'payment_type.required' => 'Please choose a payment type for this bill'
					// 'charge_reference_value.required' => 'Charge Account requires a custom tracking field value',
					// 'pickup_reference_value.required' => 'Pickup Account requires a custom tracking field value',
					//'delivery_reference_value.required' => 'Delivery Account requires a custom tracking field value'
				];

		if($oldBill) {
			$rules = array_merge($rules, ['updated_at' => 'required|date|date_equals:' . $oldBill->updated_at]);
			$messages = array_merge($messages, ['updated_at.date_equals' => 'This bill has been modified since you loaded the page. Please re-load the bill and try again']);
		}

		if($req->interliner_id != "") {
			$rules = array_merge($rules, ['interliner_id' => 'required', 'interliner_reference_value' => 'alpha_dash|min:4', 'interliner_cost' => "min:0", 'interliner_cost_to_customer' => 'min:0']);
		}

		if($req->pickup_address_type === 'Account') {
			$rules = array_merge($rules, ['pickup_account_id' => 'required|numeric']);
			$messages = array_merge($messages, ['pickup_account_id.required' => 'Pickup Account is required when address input type is Account']);
		}

		if($req->delivery_address_type === 'Account') {
			$rules = array_merge($rules, ['delivery_account_id' => 'required|numeric']);
			$messages = array_merge($messages, ['delivery_account_id.required' => 'Delivery Account is required when address input type is Account']);
		}

		if($req->payment_type == 'Account') {
			$rules = array_merge($rules, ['charge_account_id' => 'required']);
			$messages = array_merge($messages, ['charge_account_id.required' => 'Charge Account ID is required', 'charge_account_reference_value.required' => 'Charge Account requires a reference value']);
		} else if ($req->payment_type == 'Driver') {
			$rules = array_merge($rules, ['charge_driver_id' => 'required']);
			$messages = array_merge($messages, ['charge_driver_id.required' => 'Must select a driver to charge back to']);
		}
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
		//TODO - transpose payment types required fields
		$partialsRules = new \App\Http\Validation\PartialsValidationRules();
		
		$pickupAddress = $partialsRules->GetAddressMinValidationRules($req, 'pickup_address', 'Pickup');
		$rules = array_merge($rules, $pickupAddress['rules']);
		$messages = array_merge($messages, $pickupAddress['messages']);

		$deliveryAddress = $partialsRules->GetAddressMinValidationRules($req, 'delivery_address', 'Delivery');
		$rules = array_merge($rules, $deliveryAddress['rules']);
		$messages = array_merge($messages, $deliveryAddress['messages']);

    	return ['rules' => $rules, 'messages' => $messages];
    }
}

