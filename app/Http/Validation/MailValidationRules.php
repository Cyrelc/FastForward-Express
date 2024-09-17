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
            'message' => 'required'
        ];
        $messages = [];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public function GetRequestAccountValidationRules() {
        $rules = [
            'deliveryCount' => 'required',
            'email' => 'required|email',
            'phone' => 'required|alpha_dash',
            'message' => 'required|regex:/[a-zA-Z0-9\s]+/',
            'contactName' => 'required',
            'companyName' => 'required'
        ];
        $messages = [];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public function GetRequestDeliveryValidationRules() {
        $rules = [
            'email' => 'required|email|confirmed|same:email_confirmation',
            'phone' => 'required|alpha_dash',
            'contact-name' => 'required|regex:/[a-zA-Z0-9\s]+/',
            'pickup-address' => 'required|regex:/[a-zA-Z0-9\s]+/',
            'pickup-postal-code' => 'required|alpha_dash',
            'pickup-time' => 'required|date|after_or_equal:today',
            'delivery-address' => 'required|regex:/[a-zA-Z0-9\s]+/',
            'delivery-postal-code' => 'required|alpha_dash',
            'delivery-time' => 'required|numeric',
            'weight-kg' => 'required|numeric',
            'dimensions' => 'required|regex:/[a-zA-Z0-9\s]+/',
            'description' => 'regex:/[a-zA-Z0-9\s]+/'
        ];
        $messages = [];

        return ['rules' => $rules, 'messages' => $messages];
    }
}

?>
