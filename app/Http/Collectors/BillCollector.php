<?php

namespace App\Http\Collectors;

class BillCollector {
	//TODO: replace with a call to payment Types repo for maximum flexibility
	private $prepaidTypes = ['Cash', 'Visa', 'Mastercard', 'American Express', 'Cheque', 'Bank Transfer'];

	public function Collect($req, $permissions, $pickupAddressId, $deliveryAddressId, $charge_id) {
		$collectedBill = [
			'bill_id' => $req->bill_id,
			'charge_account_id' => $req->payment_type['name'] === 'Account' ? $charge_id : null,
			'charge_reference_value' => ($req->payment_type['name'] === 'Account' || $req->payment_type['required_field'] != null) ? $req->charge_reference_value : null,
			'delivery_account_id' => $req->delivery_address_type === "Account" ? $req->delivery_account_id : null,
			'delivery_address_id' => $deliveryAddressId,
			'delivery_reference_value' => $req->delivery_address_type == 'Account' ? $req->delivery_reference_value : null,
			'delivery_type' => $req->delivery_type['id'],
			'description' => $req->description,
			'is_min_weight_size' => filter_var($req->is_min_weight_size, FILTER_VALIDATE_BOOLEAN),
			'is_pallet' => filter_var($req->is_pallet, FILTER_VALIDATE_BOOLEAN),
			'packages' => json_encode($req->packages),
			'payment_type_id' => $req->payment_type['payment_type_id'],
			'pickup_account_id' => $req->pickup_address_type === "Account" ? $req->pickup_account_id : null,
			'pickup_address_id' => $pickupAddressId,
			'pickup_reference_value' => $req->pickup_address_type === 'Account' ? $req->pickup_reference_value : null,
			'time_pickup_scheduled' => (new \DateTime($req->time_pickup_scheduled))->format('Y-m-d H:i:s'),
			'time_delivery_scheduled' => (new \DateTime($req->time_delivery_scheduled))->format('Y-m-d H:i:s'),
			'use_imperial' => filter_var($req->use_imperial, FILTER_VALIDATE_BOOLEAN)
		];

		if(!$req->bill_id)
			$collectedBill = array_merge($collectedBill, ['time_call_received' => new \DateTime('now')]);

		if((!$req->bill_id && $permissions['createFull']) || (isset($permissions['editDispatch']) && $permissions['editDispatch']))
			$collectedBill = array_merge($collectedBill, [
				'bill_number' => $req->bill_number == "" ? null : $req->bill_number,
				'delivery_driver_commission' => $req->delivery_driver_commission == "" ? null : $req->delivery_driver_commission / 100,
				'delivery_driver_id' => $req->delivery_driver_id == "" ? null : $req->delivery_driver_id,
				'internal_comments' => $req->internal_comments,
				'pickup_driver_id' => $req->pickup_driver_id == "" ? null : $req->pickup_driver_id,
				'pickup_driver_commission' => $req->pickup_driver_commission == "" ? null : $req->pickup_driver_commission / 100,
				'time_call_received' => (new \DateTime($req->time_call_received))->format('Y-m-d H:i:s'),
				'time_delivered' => $req->time_delivered == "" ? null : (new \DateTime($req->time_delivered))->format('Y-m-d H:i:s'),
				'time_dispatched' => $req->time_dispatched == "" ? null : (new \DateTime($req->time_dispatched))->format('Y-m-d H:i:s'),
				'time_picked_up' => $req->time_picked_up == "" ? null : (new \DateTime($req->time_picked_up))->format('Y-m-d H:i:s')
			]);

		if((!$req->bill_id && $permissions['createFull']) || (isset($permissions['editBilling']) && $permissions['editBilling']))
			$collectedBill = array_merge($collectedBill, [
				'amount' => $req->amount == "" ? null : $req->amount,
				'chargeback_id' => $req->payment_type['name'] === 'Driver' ? $charge_id : null,
				'charge_employee_id' => $req->charge_employee_id == "" ? null : $req->charge_employee_id,
				'interliner_id' => $req->interliner_id == "" ? null : $req->interliner_id,
				'interliner_reference_value' => $req->interliner_id == "" ? null : $req->interliner_reference_value,
				'interliner_cost' => $req->interliner_id == "" ? null : $req->interliner_cost,
				'interliner_cost_to_customer' => $req->interliner_id == "" ? null : $req->interliner_cost_to_customer,
				'payment_id' => in_array($req->payment_type['name'], $this->prepaidTypes) ? $charge_id : null,
				'repeat_interval' => $req->repeat_interval ? $req->repeat_interval : null,
				'skip_invoicing' => filter_var($req->skip_invoicing, FILTER_VALIDATE_BOOLEAN),
			]);

		return $collectedBill;
	}
}
