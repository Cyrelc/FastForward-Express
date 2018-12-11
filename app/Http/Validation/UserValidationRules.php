<?php
namespace App\Http\Validation;

class UserValidationRules {
    public function GetValidationRules($req) {
        $rules = ['password' => 'required|min:8',
                'password_confirm' => 'required|same:password'];

        $messages = ['password.required' => 'Password field can not be left blank',
                    'password.min' => 'New password must be at least 8 characters in length',
                    'password_confirm.same' => 'Passwords must match',
                    'password_confirm.required' => 'Password confirmation field can not be left blank'];

        return ['rules' => $rules, 'messages' => $messages];
    }
}
