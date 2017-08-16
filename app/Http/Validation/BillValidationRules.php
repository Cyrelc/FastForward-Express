<?php
namespace App\Http\Validation;

class BillValidationRules {
    public function GetValidationRules($req) {
    	$rules = [	'delivery_date' => 'required|date',
    				'bill_number'=> 'required|unique:bills',
    				'amount' => 'required|numeric',
    				'charge_selection_submission' => 'required',
    				'pickup_use_submission' => 'required',
    				'pickup_driver_id' => 'required',
                    'pickup_driver_commission' => 'required|numeric|between:0,100',
                    'delivery_use_submission' => 'required',
                    'delivery_driver_id' => 'required',
                    'delivery_driver_commission' => 'required|numeric|between:0,100'];

    	$messages = ['delivery_date.required' => 'Delivery date is required',
                    'delivery_date.date' => 'Delivery date is in an incorrect format',
    				'bill_number.required' => 'Waybill number can not be empty', 
                    'bill_number.unique' => 'Provided waybill number is not unique. Please try again.',
    				'amount.required' => 'Bill amount can not be empty',
                    'amount.numeric' => 'Bill amount must be a numeric value',
    				'charge_selection_submission.required' => 'You must select a payment method or account to charge',
    				'pickup_use_submission.required' => 'You must select whether to use an account or address for pickup',
    				'pickup_driver_id.required' => 'Pickup driver can not be empty',
                    'pickup_driver_commission.required' => 'Pickup driver commission can not be empty',
                    'pickup_driver_commission.numeric' => 'Pickup driver commission must be a numeric value',
                    'pickup_driver_commission.between' => 'Pickup driver commission must be between 0% and 100%',
                    'delivery_use_submission.required' => 'You must select whether to use an account or address for delivery',
                    'delivery_driver_id.required' => 'Delivery driver can not be empty',
                    'delivery_driver_commission.required' => 'Delivery driver commission can not be empty',
                    'delivery_driver_commission.numeric' => 'Delivery driver commission must be a numeric value',
                    'delivery_driver_commission.between' => 'Delivery driver commission must be between 0% and 100%'];

    	switch($req->charge_selection_submission) {
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
				$messages = array_merge($messages, ['pickup_account_id.required' => 'Pickup account can not be blank']);
				break;
			case "address":
		        $partialsRules = new \App\Http\Validation\PartialsValidationRules();
		        $pickupAddress = $partialsRules->GetAddressValidationRules('pickup', 'Pickup');
		        $rules = array_merge($rules, $pickupAddress['rules']);
		        $messages = array_merge($messages, $pickupAddress['messages']);
                break;
		}

		switch($req->delivery_use_submission) {
			case "account":
				$rules = array_merge($rules, ['delivery_account_id' => 'required']);
				$messages = array_merge($rules, ['delivery_account_id.required' => 'Delivery Account can not be blank']);
                break;
			case "address":
				$partialsRules = new \App\Http\Validation\PartialsValidationRules();
				$deliveryAddress = $partialsRules->GetAddressValidationRules('delivery', 'Delivery');
				$rules = array_merge($rules, $deliveryAddress['rules']);
				$messages = array_merge($messages, $deliveryAddress['messages']);
                break;
		}

    	return ['rules' => $rules, 'messages' => $messages];
    }
}

