<?php
namespace App\Http\Validation;

use App\Rules\Password;

class UserValidationRules {
    public function GetPasswordValidationRules() {
        $rules = [
            'password' => ['required', 'min:8', 'confirmed', new Password]
        ];

        $messages = [
            'password.required' => 'Password field can not be left blank',
            'password.min' => 'New password must be at least 8 characters in length',
            'password.confirmed' => 'Passwords must match'
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }
}
