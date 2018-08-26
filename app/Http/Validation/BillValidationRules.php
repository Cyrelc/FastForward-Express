<?php
namespace App\Http\Validation;

class BillValidationRules {
    public function GetValidationRules($req) {
		$rules = [	'pickup_date_scheduled' => 'required|date',
					'delivery_date_scheduled' => 'required|date'];

    	$messages = ['pickup_date_scheduled.required' => 'Pickup date is required',
					'pickup_date_scheduled.date' => 'Delivery date is in an incorrect format',
					'delivery_date_scheduled.required' => 'Delivery date is required',
					'delivery_date_scheduled.date' => 'Delivery date is in an incorrect format'];

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

