<?php
namespace App\Http\Validation;

class ChargebackValidationRules {
    public function GetValidationRules() {
        $rules = [
            'name' => 'required',
            'amount' => 'required',
            'employees' => 'sometimes|required',
            'start_date' => 'required',
        ];

        $messages = [
            'name.required' => 'Chargeback Name is required.',
            'amount.required' => 'Please select an amount to charge back to the employee',
            'employees.required' => 'Please select at least one employee to apply the chargeback to',
            'start_date.required' => 'Please select a date to start the charges',
        ];

        return [
            'rules' => $rules,
            'messages' => $messages
        ];
    }
}
