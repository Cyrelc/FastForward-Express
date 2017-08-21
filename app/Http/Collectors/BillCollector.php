<?php

namespace App\Http\Collectors;


class BillCollector {
	public function Collect($req, $chargeAccountId, $pickupAddressId, $deliveryAddressId) { 

		switch ($req->pickup_use_submission) {
			case 'account':
				$pickup_account = $req->pickup_account_id;
				break;
			case 'address':
				$pickup_account = null;
				break;
		}

		switch ($req->delivery_use_submission) {
			case 'account':
				$delivery_account = $req->delivery_account_id;
				break;
			case 'address':
				$delivery_account = null;
				break;
		}

		switch ($req->use_interliner) {
			case 'true':
				$interliner_id = $req->interliner_id;
				$interliner_amount = $req->interliner_amount;
				break;
			case 'false':
				$interliner_id = null;
				$interliner_amount = null;
				break;
		}

		return [
			'bill_id' => $req->bill_id,
			'charge_account_id' => $chargeAccountId,
			'other_account_id' => $req->other_account_id,
			'pickup_account_id' => $pickup_account,
			'pickup_address_id' => $pickupAddressId,
			'delivery_account_id' => $delivery_account,
			'delivery_address_id' => $deliveryAddressId,
			'charge_reference_value' => $req->charge_reference_value,
			'pickup_reference_value' => $req->pickup_reference_value,
			'delivery_reference_value' => $req->delivery_reference_value,
			'pickup_driver_id' => $req->pickup_driver_id,
			'delivery_driver_id' => $req->delivery_driver_id,
			'pickup_driver_commission' => $req->pickup_driver_commission,
			'delivery_driver_commission' => $req->delivery_driver_commission,
			'interliner_id' => $interliner_id,
			'interliner_amount' => $interliner_amount,
			'bill_number' => $req->bill_number,
			'description' => $req->description,
			'date' => (new \DateTime($req->input('date')))->format('Y-m-d'),
			'amount' => $req->amount
		];
	}

	public function Remerge($req, $bill){
		$billVars = array('charge_account_id', 'other_account_id', 'pickup_account_id', 'delivery_account_id', 'amount', 'bill_number', 'pickup_driver_id', 'delivery_driver_id', 'pickup_driver_commission', 'delivery_driver_commission', 'description');

		// dd($req->old['amount'] !== null);

		foreach ($billVars as $billVar) {
			if($req->old($billVar) !== null)
				$bill->{$billVar} = $req->old($billVar);
		}

		if ($req->old('date') !== null)
			$bill->date = strtotime($req->old('date'));

		return $bill;
	}
}
