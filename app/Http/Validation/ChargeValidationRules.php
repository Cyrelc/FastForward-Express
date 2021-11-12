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
            'time_pickup_scheduled' => 'required',
            'time_delivery_scheduled' => 'required'
        ];

        $messages = [];

        return ['rules' => $rules, 'messages' => $messages];
    }
}

