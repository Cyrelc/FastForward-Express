<?php

namespace App\Http\Collectors;

class BillCollector {
	public function Collect($req, $pickupAddressId, $deliveryAddressId) { 

		$required_fields = ['payment_type', 'pickup_driver_id', 'delivery_driver_id', 'pickup_driver_commission', 'delivery_driver_commission', 'bill_number', 'pickup_date_scheduled', 'delivery_date_scheduled', 'amount', 'delivery_type'];

		switch($req->payment_type) {
			case 'account':
				$required_fields = array_merge($required_fields, ['charge_account_id']);
				break;
		}

		if($req->interliner_id != "")
			$required_fields = array_merge($required_fields, ['interliner_id', 'interliner_cost', 'interliner_cost_to_customer']);

		$count = 0;
		foreach($required_fields as $field) {
			if ($req->input($field) != null)
				$count++;
		}

		$percentage_complete = number_format($count / count($required_fields), 2);

		return [
			'bill_id' => $req->bill_id,
			'charge_account_id' => $req->charge_account_id == "" ? null : $req->charge_account_id,
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
			'interliner_cost' => $req->interliner_id == "" ? null : $req->interliner_cost,
			'interliner_cost_to_customer' => $req->interliner_id == "" ? null : $req->interliner_cost_to_customer,
			'bill_number' => $req->bill_number == "" ? null : $req->bill_number,
			'description' => $req->description,
			'pickup_date_scheduled' => (new \DateTime($req->input('pickup_date_scheduled')))->format('Y-m-d'),
			'delivery_date_scheduled' => (new \DateTime($req->delivery_date_scheduled))->format('Y-m-d'),
			'amount' => $req->amount == "" ? null : $req->amount,
			'skip_invoicing' => isset($req->skip_invoicing),
			'delivery_type' => $req->delivery_type == "" ? null : $req->delivery_type,
			'percentage_complete' => $percentage_complete
		];
	}
}
