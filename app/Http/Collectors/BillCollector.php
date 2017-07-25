<?php

namespace App\Http\Collectors;


class BillCollector {
	public function Collect($req, $chargeAccountId, $pickupAddressId, $deliveryAddressId) { 
		return [
			'charge_account_id' => $chargeAccountId,
			'other_account_id' => $req->other_account_id,
			'pickup_account_id' => $req->pickup_account_id,
			'pickup_address_id' => $pickupAddressId,
			'delivery_account_id' => $req->delivery_account_id,
			'delivery_address_id' => $deliveryAddressId,
			'charge_reference_value' => $req->charge_reference_value,
			'pickup_reference_value' => $req->pickup_reference_value,
			'delivery_reference_value' => $req->delivery_reference_value,
			'pickup_driver_id' => $req->pickup_driver_id,
			'delivery_driver_id' => $req->delivery_driver_id,
			'pickup_driver_percentage' => $req->pickup_driver_commission,
			'delivery_driver_percentage' => $req->delivery_driver_commission,
			'interliner_id' => $req->interliner_id,
			'interliner_amount' => $req->interliner_amount,
			'bill_number' => $req->bill_number,
			'description' => $req->description,
			'date' => $req->delivery_date,
			'amount' => $req->amount
		];
	}
}
