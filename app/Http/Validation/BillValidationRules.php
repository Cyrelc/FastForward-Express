<?php
namespace App\Http\Validation;

class BillValidationRules {
    public function GetValidationRules($req) {
		$rules = [	'time_pickup_scheduled' => 'required|date',
					'time_delivery_scheduled' => 'required|date',
					'time_call_received' => 'required|date',
					'time_dispatched' => 'date',
					'time_picked_up' => 'date',
					'time_delivered' => 'date',
					// 'charge_reference_value' => 'sometimes|required',
					// 'pickup_reference_value' => 'sometimes|required',
					'delivery_reference_value' => 'sometimes|required'];

    	$messages = ['time_pickup_scheduled.required' => 'Pickup date is required',
					'time_pickup_scheduled.date' => 'Pickup date is in an incorrect format',
					'time_delivery_scheduled.required' => 'Delivery date is required',
					'time_delivery_scheduled.date' => 'Delivery date is in an incorrect format',
					'time_call_received.required' => 'Call Received Time is requred',
					'time_call_received.date' => 'Call received time is in an incorrect format',
					// 'charge_reference_value.required' => 'Charge Account requires a custom tracking field value',
					// 'pickup_reference_value.required' => 'Pickup Account requires a custom tracking field value',
					'delivery_reference_value.required' => 'Delivery Account requires a custom tracking field value'];

		if($req->interliner_id != "") {
			$rules = array_merge($rules, ['interliner_reference_value' => 'alpha_dash|min:4', 'interliner_cost' => "min:0", 'interliner_cost_to_customer' => 'min:0']);
		}

		if($req->charge_type == 'account') {
			$rules = array_merge($rules, ['charge_account_id' => 'required']); //, 'charge_account_reference_value' => 'sometimes|required']);
			$messages = array_merge($messages, ['charge_account_id.required' => 'Charge Account ID is required', 'charge_account_reference_value.required' => 'Charge Account requires a reference value']);
		} else if ($req->charge_type == 'driver') {
			$rules = array_merge($rules, ['charge_driver_id' => 'required']);
			$messages = array_merge($messages, ['charge_driver_id.required' => 'Must select a driver to charge back to']);
		} else if ($req->charge_type == 'prepaid') {
			$rules = array_merge($rules, ['prepaid_type' => 'required', 'prepaid_reference_value' => 'sometimes|required|min:4']);
			$messages = array_merge($messages, ['prepaid_type.required' => 'Must select a payment type for "prepaid"', 'prepaid_reference_value.required' => 'Selected prepaid type requires a reference value', 'prepaid_reference_value.min' => 'Prepaid reference value must be at least four characters in length']);
		}

		$partialsRules = new \App\Http\Validation\PartialsValidationRules();
		
		$pickupAddress = $partialsRules->GetAddressValidationRules('pickup', 'Pickup');
		$rules = array_merge($rules, $pickupAddress['rules']);
		$messages = array_merge($messages, $pickupAddress['messages']);

		$deliveryAddress = $partialsRules->GetAddressValidationRules('delivery', 'Delivery');
		$rules = array_merge($rules, $deliveryAddress['rules']);
		$messages = array_merge($messages, $deliveryAddress['messages']);

		$package_names = [];
        foreach($req->all() as $key => $value) {
            if(substr_compare($key, 'package', 0, 7) == 0 && !in_array(explode('_', $key, 2)[0], $package_names))
                array_push($package_names, explode('_', $key, 2)[0]);
		}
		if(count($package_names) < 1) {
			$rules = array_merge($rules, ['package' => 'required']);
			$messages = array_merge($messages, ['package.required' => 'At least one package is required']);
		}
		foreach($package_names as $package_name) {
			$package_validation = $partialsRules->GetPackageValidationRules($req, $package_name);
			$rules = array_merge($rules, $package_validation['rules']);
			$messages = array_merge($messages, $package_validation['messages']);
		}

    	return ['rules' => $rules, 'messages' => $messages];
    }
}

