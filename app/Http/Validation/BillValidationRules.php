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
					'charge_reference_value' => 'sometimes|required',
					'pickup_reference_value' => 'sometimes|required',
					'delivery_reference_value' => 'sometimes|required'];

    	$messages = ['time_pickup_scheduled.required' => 'Pickup date is required',
					'time_pickup_scheduled.date' => 'Delivery date is in an incorrect format',
					'time_delivery_scheduled.required' => 'Delivery date is required',
					'time_delivery_scheduled.date' => 'Delivery date is in an incorrect format',
					'time_call_received.required' => 'Call Received Time is requred',
					'time_call_received.date' => 'Call received time is in an incorrect format',
					'charge_reference_value.required' => 'Charge Account requires a custom tracking field value',
					'pickup_reference_value.required' => 'Pickup Account requires a custom tracking field value',
					'delivery_reference_value.required' => 'Delivery Account requires a custom tracking field value'];

		if($req->interliner_id != "") {
			$rules = array_merge($rules, ['interliner_reference_value' => 'required|alpha_dash|min:4', 'interliner_cost' => "required|min:0", 'interliner_cost_to_customer' => 'required|min:0']);
			$messages = array_merge($messages, ['interliner_cost.required' => 'Must enter an interliner cost', 'interliner_cost_to_customer.required' => 'Must enter an interliner cost to customer']);
		}

		$partialsRules = new \App\Http\Validation\PartialsValidationRules();
		
		$pickupAddress = $partialsRules->GetAddressValidationRules('pickup', 'Pickup');
		$rules = array_merge($rules, $pickupAddress['rules']);
		$messages = array_merge($messages, $pickupAddress['messages']);

		$deliveryAddress = $partialsRules->GetAddressValidationRules('delivery', 'Delivery');
		$rules = array_merge($rules, $deliveryAddress['rules']);
		$messages = array_merge($messages, $deliveryAddress['messages']);

    	return ['rules' => $rules, 'messages' => $messages];
    }
}

