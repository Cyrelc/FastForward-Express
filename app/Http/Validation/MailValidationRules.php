<?php
namespace App\Http\Validation;

use App\Http\Repos;
use Illuminate\Validation\Rule;

class MailValidationRules {
    public function GetContactUsValidationRules() {
        $rules = [
            'email' => 'required|email',
            'phone' => 'required|alpha_dash',
            'subject' => 'required|regex:/[a-zA-Z0-9\s]+/',
            'message' => 'required|regex:/[a-zA-Z0-9\s]+/'
        ];
        $messages = [];

        return ['rules' => $rules, 'messages' => $messages];
    }
}

?>
