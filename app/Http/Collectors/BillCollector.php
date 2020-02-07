<?php

namespace App\Http\Collectors;

class BillCollector {
	public function Collect($req, $pickupAddressId, $deliveryAddressId, $charge_id) { 
//TODO: Only collect some fields if user is not an admin
		if($req->bill_id === "" && new \DateTime($req->time_dispatched) > new \DateTime($req->time_call_received))
			$req->time_dispatched === $req->time_call_received;

		$requiredFields = CheckRequiredFields($req);

		$percentage_complete = number_format((count($requiredFields['required']) - count($requiredFields['incomplete'])) / count($requiredFields['required']), 2);

		return [
			'amount' => $req->amount == "" ? null : $req->amount,
			'bill_id' => $req->bill_id,
			'bill_number' => $req->bill_number == "" ? null : $req->bill_number,
			'charge_account_id' => $req->payment_type['name'] === 'Account' ? $charge_id : null,
			'charge_reference_value' => ($req->payment_type['name'] === 'Account' || $req->payment_type['required_field'] != null) ? $req->charge_reference_value : null,
			'chargeback_id' => $req->payment_type['name'] === 'Driver' ? $charge_id : null,
			'description' => $req->description,
			'delivery_account_id' => $req->delivery_address_type === "Account" ? $req->delivery_account_id : null,
			'delivery_address_id' => $deliveryAddressId,
			'delivery_driver_commission' => $req->delivery_driver_commission == "" ? null : $req->delivery_driver_commission / 100,
			'delivery_driver_id' => $req->delivery_driver_id == "" ? null : $req->delivery_driver_id,
			'delivery_reference_value' => $req->delivery_address_type == 'Account' ? $req->delivery_reference_value : null,
			'delivery_type' => $req->delivery_type['id'],
			'incomplete_fields' => $percentage_complete === 1 ? null : json_encode($requiredFields['incomplete']),
			'interliner_id' => $req->interliner_id == "" ? null : $req->interliner_id,
			'interliner_reference_value' => $req->interliner_id == "" ? null : $req->interliner_reference_value,
			'interliner_cost' => $req->interliner_id == "" ? null : $req->interliner_cost,
			'interliner_cost_to_customer' => $req->interliner_id == "" ? null : $req->interliner_cost_to_customer,
			'is_min_weight_size' => $req->is_min_weight_size === 'true' ? 1 : 0,
			'is_pallet' => $req->is_pallet,
			'packages' => $req->is_min_weight_size === true ? null : json_encode($req->packages),
			'payment_id' => in_array($req->payment_type['name'], $prepaidTypes) ? $charge_id : null,
			'payment_type_id' => $req->payment_type['payment_type_id'],
			'percentage_complete' => $percentage_complete,
			'pickup_account_id' => $req->pickup_address_type === "Account" ? $req->pickup_account_id : null,
			'pickup_address_id' => $pickupAddressId,
			'pickup_reference_value' => $req->pickup_address_type === 'Account' ? $req->pickup_reference_value : null,
			'pickup_driver_id' => $req->pickup_driver_id == "" ? null : $req->pickup_driver_id,
			'pickup_driver_commission' => $req->pickup_driver_commission == "" ? null : $req->pickup_driver_commission / 100,
			'skip_invoicing' => $req->skip_invoicing,
			'time_pickup_scheduled' => new \DateTime($req->time_pickup_scheduled),
			'time_delivery_scheduled' => new \DateTime($req->time_delivery_scheduled),
			'time_call_received' => new \DateTime($req->time_call_received),
			'time_dispatched' => $req->time_dispatched == "" ? null : new \DateTime($req->time_dispatched),
			'time_picked_up' => $req->time_picked_up == "" ? null : new \DateTime($req->time_picked_up),
			'time_delivered' => $req->time_delivered == "" ? null : new \DateTime($req->time_delivered),
			'use_imperial' => $req->use_imperial
		];
	}

	private function CheckRequiredFields($req) {
		$requiredFields = [
			'amount',
			'bill_number',
			'delivery_driver_commission',
			'delivery_driver_id',
			'delivery_type',
			'is_min_weight_size',
			'is_pallet',
			'pickup_driver_id',
			'pickup_driver_commission',
			'time_pickup_scheduled',
			'time_delivery_scheduled',
			'time_call_received',
			'time_dispatched',
			'use_imperial',
		];

		$prepaidTypes = ['Cash', 'Visa', 'Mastercard', 'American Express', 'Cheque', 'Bank Transfer'];

		if($req->interliner_id != "")
			$requiredFields = array_merge($requiredFields, ['interliner_id', 'interliner_reference_value', 'interliner_cost', 'interliner_cost_to_customer']);

		if($req->payment_type['name'] === 'Account')
			$requiredFields = array_merge($requiredFields, ['charge_account_id']);
		elseif($req->payment_type['name'] === 'Driver')
			$requiredFields = array_merge($requiredFields, ['charge_driver_id']);
		elseif(in_array($req->payment_type['name'], $prepaidTypes))
			$requiredFields = array_merge($requiredFields, ['payment_id']);

		$incompleteFields = [];
		foreach($requiredFields as $field) {
			if ($req->input($field) == null || $req->input($field) == '')
				array_push($incompleteFields, $field);
		}

		return ['required' => $requiredFields, 'incomplete' => $incompleteFields];
	}
}
