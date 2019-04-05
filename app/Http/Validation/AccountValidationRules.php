<?php
namespace App\Http\Validation;

class AccountValidationRules {
    public function GetValidationRules($req) {
        $rules = [
            'account_number' => 'required|unique:accounts,account_number,' . $req->account_id . ',account_id',
            'account_name' => 'required',
            'invoice_interval' => 'required|exists:selections,value',
            'parent-account-id' => 'exists:accounts,account_id',
            'discount' => 'nullable|numeric|between:0,100',
            'start_date' => 'required|date',
            'min_invoice_amount' => 'nullable|numeric',
            'fuel_surcharge' => 'nullable|numeric',
        ];

        $messages = [
            'account_number.required' => 'Account Number is required',
            'account_number.unique' => 'Account Number must be unique',
            'account_name.required' => 'Company Name is required.',
            'discount.numeric' => 'Discount must be a number',
            'discount.between' => 'Discount value is invalid'
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }
}

