<?php
namespace App\Http\Validation;

class SelectionValidationRules {
    public function GetValidationRules() {
        $rules = [
            'value' => ['required', 'min:5', 'unique:selections', 'max:255'],
            'type' => ['required', 'exists:selections']
        ];

        $messages = [];

        return ['rules' => $rules, 'messages' => $messages];
    }
}

