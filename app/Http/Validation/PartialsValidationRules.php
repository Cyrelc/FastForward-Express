<?php
namespace app\Http\Validation;

class PartialsValidationRules {
    public function GetAddressValidationRules($prefix, $prefix_name) {
        return [
            'rules' => [
                $prefix . '-street' => 'required',
                $prefix . '-city' => 'required',
                $prefix . //Regex used found here: http://regexlib.com/REDetails.aspx?regexp_id=417
                $prefix . '-zip-postal' => ['required', 'regex:/^((\d{5}-\d{4})|(\d{5})|([AaBbCcEeGgHhJjKkLlMmNnPpRrSsTtVvXxYy]\d[A-Za-z]\s?\d[A-Za-z]\d))$/'],
                $prefix . '-state-province' => 'required',
                $prefix . '-country' => 'required',
            ],
            'messages' => [
                $prefix . '-street.required' => $prefix_name . ' Address Street is required.',
                $prefix . '-city.required' => $prefix_name . ' Address City is required.',
                $prefix . '-zip-postal.required' => $prefix_name . ' Address Postal Code is required.',
                $prefix . '-zip-postal.regex' => $prefix_name . ' Postal Code must be in the format "Q4B 5C5", "501-342", or "123324".',
                $prefix . '-state-province.required' => $prefix_name . ' Province is required.',
                $prefix . '-country.required' => $prefix_name . ' Country is required.',
            ]
        ];
    }

    public function GetContactValidationRules($prefix, $prefix_name) {
        return [
            'rules' => [
                $prefix . '-first-name' => 'required',
                $prefix . '-last-name' => 'required',
                //Regex used found here: http://www.regexlib.com/REDetails.aspx?regexp_id=607
                $prefix . '-phone1' => ['required', 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
                $prefix . '-phone2' => ['regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
                $prefix . '-email1' => 'required|email',
                $prefix . '-email2' => 'email'
            ],
            'messages' => [
                $prefix . '-first-name.required' => $prefix_name . ' First Name is required.',
                $prefix . '-last-name.required' => $prefix_name . ' Last Name is required.',
                $prefix . '-phone1.required' => $prefix_name . ' Primary Phone Number is required.',
                $prefix . '-phone1.regex' => $prefix_name . ' Primary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
                $prefix . '-phone2.regex' => $prefix_name . ' Secondary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
                $prefix . '-email1.required' => $prefix_name . ' Primary Email is required.',
                $prefix . '-email1.email' => $prefix_name . ' Primary Email must be an email.',
                $prefix . '-email2.email' => $prefix_name . ' Secondary Email must be an email.',
            ]
        ];
    }

    public function GetCommissionValidationRules($prefix, $prefix_name, $hasDepreciation) {
        $validationRules = [
            $prefix . '-employee-id' => 'required',
            $prefix . '-percent' => 'required|numeric'
        ];

        $validationMessages = [
            $prefix . '-employee-id' => $prefix_name . ' Driver is required.',
            $prefix . '-percent.required' => $prefix_name . '% value is required.',
            $prefix . '-percent.numeric' => $prefix_name . ' % must be a number.'
        ];

        if ($hasDepreciation) {
            $validationRules = array_merge($validationRules,[
                $prefix . 'depreciate-percent' => 'required',
                $prefix . 'depreciate-duration' => 'required',
                $prefix . 'depreciate-start-date' => 'required']);
            $validationMessages = array_merge($validationMessages, [
                $prefix . 'depreciate-percent' => $prefix_name . ' depreciation percentage cannot be blank',
                $prefix . 'depreciate-duration' => $prefix_name . ' depreciation duration cannot be blank',
                $prefix . 'depreciate-start-date' => $prefix_name . ' depreciation start date cannot be blank']);
        }

        return [
            'rules' => $validationRules,
            'messages' => $validationMessages
        ];
    }
}
