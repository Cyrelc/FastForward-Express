<?php
namespace App\Http\Validation;

class BillValidationRules {
    public function GetValidationRules($req) {
		$rules = [	'pickup_date_scheduled' => 'required|date',
					'delivery_date_scheduled' => 'required|date',
					'charge_reference_value' => 'sometimes|required',
					'pickup_reference_value' => 'sometimes|required',
					'delivery_reference_value' => 'sometimes|required'];

    	$messages = ['pickup_date_scheduled.required' => 'Pickup date is required',
					'pickup_date_scheduled.date' => 'Delivery date is in an incorrect format',
					'delivery_date_scheduled.required' => 'Delivery date is required',
					'delivery_date_scheduled.date' => 'Delivery date is in an incorrect format',
					'charge_reference_value.required' => 'Charge Account requires a custom tracking field value',
					'pickup_reference_value.required' => 'Pickup Account requires a custom tracking field value',
					'delivery_reference_value.required' => 'Delivery Account requires a custom tracking field value'];

		if($req->interliner_id != "") {
			$rules = array_merge($rules, ['interliner_cost' => "required|min:0", 'interliner_cost_to_customer' => 'required|min:0']);
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

