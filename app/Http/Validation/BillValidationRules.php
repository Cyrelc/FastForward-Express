<?php
namespace App\Http\Validation;

class BillValidationRules {
    public function GetValidationRules($req) {
    	$rules = [	'delivery_date' => 'required|date',
    				'bill_number'=> 'required',
    				'amount' => 'required|numeric',
    				'selected_charge' => 'required',
    				'pickup_use_submission' => 'required',
    				'delivery_use_submission' => 'required',
    				'delivery_driver_id' => 'required',
    				'pickup_driver_commission' => 'required|numeric|'
    				'pickup_driver_id' => 'required'];

    	$messages = ['delivery_date.required' => 'Bill date is required',
    				'bill_number.required' => 'Waybill number can not be empty', 
    				'amount.required' => "Bill amount can not be empty", 'amount.numeric' => 'Bill amount must be a numeric value',
    				'selected_charge.required'=>'You must select a payment method or account to charge',
    				'pickup_use_submission.required' => 'You must select whether to use an account or address for pickup',
    				'delivery_use_submission.required' => 'You must select whether to use an account or address for delivery',
    				'delivery_driver_id.required' => 'Delivery Driver can not be empty',
    				'pickup_driver_id.required' => 'Pickup Driver can not be empty'];

    	switch($req->selected_charge) {
    		case "pickup_account":
    			$rules = array_merge($rules, ['pickup_account_id' => 'required']);
    			$messages = array_merge($messages, ['pickup_account_id.required' => 'Pickup account can not be blank']);
    			break;
    		case "delivery_account":
    			$rules = array_merge($rules, ['delivery_account_id' => 'required']);
    			$messages = array_merge($messages, ['delivery_account_id.required' => 'Delivery account can not be blank']);
    			break;
    		case "other_account":
    			$rules = array_merge($rules, ['charge_account_id' => 'required']);
    			$messages = array_merge($messages, ['charge_account_id.required' => 'Charge account can not be blank']);
    			break;
    		case "pre-paid":
    			$rules = array_merge($rules, ['payment_type' => 'required']);
    			$messages = array_merge($messages, ['payment_type.required' => 'Payment type can not be blank']);
    		default:
    			break;
    	}

		switch($req->pickup_use_submission) {
			case "account":
				$rules = array_merge($rules, ['pickup_account_id' => 'required']);
				$messages = array_merge($messages, ['pickup_account_id' => 'Pickup account can not be blank']);
				break;
			case "address":
		        $partialsRules = new \App\Http\Validation\PartialsValidationRules();
		        $pickupAddress = $partialsRules->GetAddressValidationRules('pickup', 'Pickup');
		        $rules = array_merge($rules, $pickupAddress['rules']);
		        $messages = array_merge($messages, $pickupAddress['messages']);
		}

		switch($req->delivery_use_submission) {
			case "account":
				$rules = array_merge($rules, ['delivery_account_id' => 'required']);
				$messages = array_merge($rules, ['delivery_account_id.required' => 'Delivery Account can not be blank']);
			case "address":
				$partialsRules = new \App\Http\Validation\PartialsValidationRules();
				$deliveryAddress = $partialsRules->GetAddressValidationRules('delivery', 'Delivery');
				$rules = array_merge($rules, $deliveryAddress['rules']);
				$messages = array_merge($messages, $deliveryAddress['messages']);
		}

    	return ['rules' => $rules, 'messages' => $messages];
    }
}

