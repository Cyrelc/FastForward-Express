<?php
namespace App\Http\Validation;

class InterlinerValidationRules {
    public function GetValidationRules($req) {
    	$rules = ['name' => 'required'];
    	$messages = ['name.required' => 'The Interliner name cannot be blank'];

        $partialsRules = new \App\Http\Validation\PartialsValidationRules();
        $address = $partialsRules->GetAddressMinValidationRules($req, 'address', 'Interliner');

        $rules = array_merge($rules, $address['rules']);
        $messages = array_merge($messages, $address['messages']);

		return ['rules' => $rules, 'messages' => $messages];
    }
}
?>
