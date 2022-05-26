<?php
namespace App\Http\Validation;

class UserValidationRules {
    public function GetValidationRules($req) {
        $rules = [
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password'
        ];

        $messages = [
            'password.required' => 'Password field can not be left blank',
            'password.min' => 'New password must be at least 8 characters in length',
            'password_confirm.same' => 'Passwords must match',
            'password_confirm.required' => 'Password confirmation field can not be left blank'
        ];

        if(strlen($req->password) < 20) {
            $rules = array_merge($rules, ['password' => 'regex:"^(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*_?<>0-9])[a-zA-Z\d\w\W].{8,}$"']);
            $messages = array_merge($messages, ['password.regex' => 'Password is too weak. Please review the password requirements and try again']);
        } else {
            $rules = array_merge($rules, ['password' => 'regex:"^(?=.*[a-z])(?=.*[A-Z])[a-zA-Z\d\w\W].{8,}$"']);
            $messages = array_merge($messages, ['password.regex' => 'Password is too weak. Please review the password requirements and try again']);
        }

        return ['rules' => $rules, 'messages' => $messages];
    }
}
