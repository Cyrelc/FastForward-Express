<?php

namespace App\Http\Collectors;

use App\Http\Repos;

class BillCollector {
	public function Collect($req, $permissions, $pickupAddressId, $deliveryAddressId) {
		$collectedBill = [
			'bill_id' => $req->bill_id,
			'delivery_account_id' => $req->delivery_address_type === "Account" ? $req->delivery_account_id : null,
			'delivery_address_id' => $deliveryAddressId,
			'delivery_reference_value' => $req->delivery_address_type == 'Account' ? trim($req->delivery_reference_value) : null,
			'delivery_type' => $req->delivery_type['id'],
			'description' => $req->description,
			'is_min_weight_size' => filter_var($req->is_min_weight_size, FILTER_VALIDATE_BOOLEAN),
			'is_pallet' => filter_var($req->is_pallet, FILTER_VALIDATE_BOOLEAN),
			'packages' => json_encode($req->packages),
			'pickup_account_id' => $req->pickup_address_type === "Account" ? $req->pickup_account_id : null,
			'pickup_address_id' => $pickupAddressId,
			'pickup_reference_value' => $req->pickup_address_type === 'Account' ? trim($req->pickup_reference_value) : null,
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
				'interliner_id' => $req->interliner_id == "" ? null : $req->interliner_id,
				'interliner_reference_value' => $req->interliner_id == "" ? null : trim($req->interliner_reference_value),
				'interliner_cost' => $req->interliner_id == "" ? null : $req->interliner_cost,
				'interliner_cost_to_customer' => $req->interliner_id == "" ? null : $req->interliner_cost_to_customer,
				'repeat_interval' => $req->repeat_interval ? $req->repeat_interval : null,
				'skip_invoicing' => filter_var($req->skip_invoicing, FILTER_VALIDATE_BOOLEAN),
			]);

		return $collectedBill;
	}

	public function CollectCharges($req, $billId) {
		$paymentRepo = new Repos\PaymentRepo();

		if(!isset($req->charges))
			return [];

		$charges = array();

		foreach($req->charges as $charge) {
			$chargeType = $paymentRepo->GetPaymentType($charge['chargeType']['payment_type_id']);
			// Manifest Id and Invoice ID are not meant to be overwritten by the bills page so these are not collected here deliberately
			// and should not be updated by the LineItemRepo later
			$temp = [
				'bill_id' => $billId,
				'charge_id' => isset($charge['charge_id']) ? $charge['charge_id'] : null,
				'charge_reference_value' => trim($charge['charge_reference_value']),
				'charge_reference_value_label' => $charge['charge_reference_value_label'],
				'charge_reference_value_required' => filter_var($charge['charge_reference_value_required'], FILTER_VALIDATE_BOOLEAN),
				'charge_type_id' => $chargeType->payment_type_id,
				'line_items' => array(),
				'to_be_deleted' => isset($charge['toBeDeleted']) && filter_var($charge['toBeDeleted'], FILTER_VALIDATE_BOOLEAN)
			];

			if($chargeType->name === 'Account')
				$temp['charge_account_id'] = $charge['charge_account_id'];
			else if ($chargeType->name === 'Employee')
				$temp['charge_employee_id'] = $charge['charge_employee_id'];

			// Process line items
			if(isset($charge['lineItems']))
				foreach($charge['lineItems'] as $lineItem) {
					$temp['line_items'][] = [
						'driver_amount' => isset($lineItem['driver_amount']) ? $lineItem['driver_amount'] : 0,
						'line_item_id' => isset($lineItem['line_item_id']) ? $lineItem['line_item_id'] : null,
						'name' => $lineItem['name'],
						'paid' => filter_var($lineItem['paid'], FILTER_VALIDATE_BOOLEAN),
						'price' => isset($lineItem['price']) ? $lineItem['price'] : 0,
						'to_be_deleted' => $temp['to_be_deleted'] ? true : (isset($lineItem['toBeDeleted']) ? filter_var($lineItem['toBeDeleted'], FILTER_VALIDATE_BOOLEAN) : false),
						'type' => $lineItem['type']
					];
				}

			$charges[] = $temp;
		}

		return $charges;
	}

	public function CreateChargeForAccountUser($req, $billId) {
		$accountRepo = new Repos\AccountRepo();
		$paymentRepo = new Repos\PaymentRepo();

		$myAccounts = $accountRepo->GetMyAccountIds($req->user(), $req->user()->can('bills.create.basic.children'));
		if(isset($req->charge_account_id) && in_array($req->charge_account_id, $myAccounts)) {
			$chargeAccountId = $req->charge_account_id;
			$chargeReferenceValue = trim($req->charge_reference_value);
		} else if(isset($req->pickup_account_id) && in_array($req->pickup_account_id, $myAccounts)) {
			$chargeAccountId = $req->pickup_account_id;
			$chargeReferenceValue = trim($req->pickup_reference_value);
		} else if(isset($req->delivery_account_id) && in_array($req->delivery_account_id, $myAccounts)) {
			$chargeAccountId = $req->delivery_account_id;
			$chargeReferenceValue = trim($req->delivery_reference_value);
		} else
			abort(403, 'Attempting to create bill and charge to account not owned by the user');

		$chargeAccount = $accountRepo->GetById($chargeAccountId);

		return array([
			'bill_id' => $billId,
			'charge_account_id' => $chargeAccount->account_id,
			'charge_id' => isset($charge['charge_id']) ? $charge['charge_id'] : null,
			'charge_reference_value' => trim($chargeReferenceValue),
			'charge_reference_value_label' => $chargeAccount->custom_field,
			'charge_reference_value_required' => filter_var($chargeAccount->is_custom_field_mandatory, FILTER_VALIDATE_BOOLEAN),
			'charge_type_id' => $paymentRepo->GetAccountPaymentType()->payment_type_id,
			'line_items' => array(),
			'to_be_deleted' => false
		]);
	}
}
