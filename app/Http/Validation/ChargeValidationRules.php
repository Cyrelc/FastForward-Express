<?php
namespace App\Http\Validation;

use App\Http\Repos;
use Illiminate\Validation\Rule;

class ChargeValidationRules {
    public function GenerateChargesValidationRules($req) {
        $ratesheetRepo = new Repos\RatesheetRepo();

        $rules = [
            'charge_acount_id' => 'required_if:ratesheet_id,null|exists:accounts,account_id',
            'ratesheet_id' => 'required_if:charge_account_id,null|exists:ratesheets,ratesheet_id',
            'delivery_type_id' => 'required|exists:selections,value',
            'pickup_address.lat' => 'required|numeric',
            'pickup_address.lng' => 'required|numeric',
            'delivery_address.lat' => 'required|numeric',
            'delivery_address.lng' => 'required|numeric',
            'packages' => 'required',
            'package_is_minimum' => 'required',
            'package_is_pallet' => 'required',
            'time_pickup_scheduled' => 'required',
            'time_delivery_scheduled' => 'required'
        ];

        $messages = [];

		if(!filter_var($req->package_is_minimum, FILTER_VALIDATE_BOOLEAN)) {
			$rules = array_merge($rules, [
				'packages' => 'required',
				'packages.*.count' => 'required|integer|min:1',
				'packages.*.weight' => 'required|numeric|min:1',
				'packages.*.length' => 'required|numeric|min:1',
				'packages.*.width' => 'required|numeric|min:1',
				'packages.*.height' => 'required|numeric|min:1'
			]);
			$messages = array_merge($messages, []);
		}

        return ['rules' => $rules, 'messages' => $messages];
    }
}

