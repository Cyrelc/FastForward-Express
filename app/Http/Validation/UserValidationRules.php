<?php
namespace App\Http\Validation;

use App\Rules\Password;

class UserValidationRules {
    public function GetPasswordValidationRules() {
        $rules = [
            'password' => ['required', 'min:8', new Password],
            'password_confirm' => 'required|same:password'
        ];

        $messages = [
            'password.required' => 'Password field can not be left blank',
            'password.min' => 'New password must be at least 8 characters in length',
            'password_confirm.same' => 'Passwords must match',
            'password_confirm.required' => 'Password confirmation field can not be left blank'
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }
}
