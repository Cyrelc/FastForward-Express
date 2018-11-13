<?php

namespace App\Http\Collectors;

class BillCollector {
	public function Collect($req, $pickupAddressId, $deliveryAddressId, $charge_id) { 

		$required_fields = ['pickup_driver_id', 'delivery_driver_id', 'pickup_driver_commission', 'delivery_driver_commission', 'bill_number', 'time_pickup_scheduled', 'time_delivery_scheduled', 'amount', 'delivery_type', 'time_call_received', 'time_dispatched'];

		if($req->interliner_id != "")
			$required_fields = array_merge($required_fields, ['interliner_id', 'interliner_reference_value', 'interliner_cost', 'interliner_cost_to_customer']);

		if($req->charge_type == 'account')
			$required_fields = array_merge($required_fields, ['charge_account_id']);
		elseif($req->charge_type == 'driver')
			$required_fields = array_merge($required_fields, ['chargeback_id']);
		elseif($req->charge_type == 'prepaid')
			$required_fields = array_merge($required_fields, ['payment_id']);

		$count = 0;
		foreach($required_fields as $field) {
			if ($req->input($field) != null)
				$count++;
		}

		$percentage_complete = number_format($count / count($required_fields), 2);

		return [
			'bill_id' => $req->bill_id,
			'charge_account_id' => $req->charge_type == 'account' ? $charge_id : null,
			'chargeback_id' => $req->charge_type == 'driver' ? $charge_id : null,
			'payment_id' => $req->charge_type == 'prepaid' ? $charge_id : null,
			'pickup_account_id' => $req->pickup_account_id == "" ? null : $req->pickup_account_id,
			'pickup_address_id' => $pickupAddressId,
			'delivery_account_id' => $req->delivery_account_id == "" ? null : $req->delivery_account_id,
			'delivery_address_id' => $deliveryAddressId,
			'charge_reference_value' => $req->charge_reference_value,
			'pickup_reference_value' => $req->pickup_reference_value,
			'delivery_reference_value' => $req->delivery_reference_value,
			'pickup_driver_id' => $req->pickup_driver_id == "" ? null : $req->pickup_driver_id,
			'delivery_driver_id' => $req->delivery_driver_id == "" ? null : $req->delivery_driver_id,
			'pickup_driver_commission' => $req->pickup_driver_commission == "" ? null : $req->pickup_driver_commission / 100,
			'delivery_driver_commission' => $req->delivery_driver_commission == "" ? null : $req->delivery_driver_commission / 100,
			'interliner_id' => $req->interliner_id == "" ? null : $req->interliner_id,
			'interliner_reference_value' => $req->interliner_id == "" ? null : $req->interliner_reference_value,
			'interliner_cost' => $req->interliner_id == "" ? null : $req->interliner_cost,
			'interliner_cost_to_customer' => $req->interliner_id == "" ? null : $req->interliner_cost_to_customer,
			'bill_number' => $req->bill_number == "" ? null : $req->bill_number,
			'description' => $req->description,
			'time_pickup_scheduled' => new \DateTime($req->time_pickup_scheduled),
			'time_delivery_scheduled' => new \DateTime($req->time_delivery_scheduled),
			'time_call_received' => new \DateTime($req->time_call_received),
			'time_dispatched' => $req->time_dispatched == "" ? null : new \DateTime($req->time_dispatched),
			'time_picked_up' => $req->time_picked_up == "" ? null : new \DateTime($req->time_picked_up),
			'time_delivered' => $req->time_delivered == "" ? null : new \DateTime($req->time_delivered),
			'amount' => $req->amount == "" ? null : $req->amount,
			'skip_invoicing' => isset($req->skip_invoicing),
			'delivery_type' => $req->delivery_type == "" ? null : $req->delivery_type,
			'percentage_complete' => $percentage_complete
		];
	}
}
