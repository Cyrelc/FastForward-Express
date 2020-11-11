<?php
namespace App\Http\Validation;

class ChargebackValidationRules {
    public function GetValidationRules() {
        $rules = [
            'amount' => 'required',
            'continuous' => 'required',
            'count_remaining' => 'exclude_if:continuous,"true"|required|integer|min:1',
            'employee_ids' => 'required',
            'name' => 'required',
            'start_date' => 'required',
        ];

        $messages = [
            'name.required' => 'Chargeback Name is required.',
            'amount.required' => 'Please select an amount to charge back to the employee',
            'employee_ids.required' => 'Please select at least one employee to apply the chargeback to',
            'start_date.required' => 'Please select a date to start the charges',
            'count_remaining.min' => 'If chargeback is not continuous, count remaining must be at least 1'
        ];

        return [
            'rules' => $rules,
            'messages' => $messages
        ];
    }
}
