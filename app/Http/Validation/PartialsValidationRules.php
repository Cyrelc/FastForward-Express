<?php
namespace app\Http\Validation;

class PartialsValidationRules {
    public function GetAddressValidationRules($prefix, $prefix_name) {
        return [
            'rules' => [
                $prefix . '-street' => 'required',
                $prefix . '-city' => 'required',
                //Regex used found here: http://regexlib.com/REDetails.aspx?regexp_id=417
                $prefix . '-zip-postal' => ['required', 'regex:/^[ABCEGHJKLMNPRSTVXY][0-9][ABCEGHJKLMNPRSTVWXYZ][ -]?[0-9][ABCEGHJKLMNPRSTVWXYZ][0-9]/'],
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

    public function GetContactValidationRules($req, $withEmails = false, $withPhones = false, $withAddress = false) {
            $rules = [
                'first_name' => 'required',
                'last_name' => 'required',
            ];
            $messages = [
                'first_name.required' => 'User first name field can not be empty',
                'last_name.required' => 'User last name field can not be empty',
            ];

            if($withEmails) {
                $temp = $this->GetEmailValidationRules($req);
                $rules = array_merge($rules, $temp['rules']);
                $messages = array_merge($messages, $temp['messages']);
            }
            if($withPhones) {
                $temp = $this->GetPhoneValidationRules($req);
                $rules = array_merge($rules, $temp['rules']);
                $messages = array_merge($messages, $temp['messages']);
            }
            if($withAddress) {
//add optional support for address
            }

            return ['rules' => $rules, 'messages' => $messages];
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

    public function GetPackageValidationRules($req, $package_name) {
        return [
            'rules' => [
                $package_name . '_count' => 'required|numeric|min:1',
                $package_name . '_weight' => 'required|numeric|min:0.001',
                $package_name . '_height' => 'required|numeric|min:0.1',
                $package_name . '_length' => 'required|numeric|min:0.1',
                $package_name . '_width' => 'required|numeric|min:0.1'
            ],
            'messages' => [
                $package_name . '_count.min' => 'Must include at least one instance of package',
                $package_name . '_weight.min' => 'Package weight must be greater than zero',
                $package_name . '_height.min' => 'Package height must be greater than zero',
                $package_name . '_length.min' => 'Package length must be greater than zero',
                $package_name . '_width.min' => 'Package width must be greater than zero'
            ]
        ];
    }

    public function GetEmailValidationRules($req) {
        $contact_id = isset($req->contact_id) ? $req->contact_id : null;
        $rules = [
            'email' => 'required',
            'email_is_primary' => 'required',
            'email.*' => 'required_unless:email_action.*,delete|email|unique:email_addresses,email,' . $contact_id . ',contact_id'
        ];
        $messages = [
            'email_is_primary.required' => 'Must select a primary email address'
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public function GetPhoneValidationRules($req) {
        $rules = [
            'phone' => 'required|array',
            'phone_is_primary' => 'required',
            'phone.*' => ['required_unless:phone_action.*,delete','regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'extension.*' => 'numeric'
        ];
        $messages = [
            'phone.required' => 'Phone Number must not be blank. Please delete empty phone numbers if that is your intention',
            'phone.regex' => 'Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'phone_is_primary.required' => 'Must select a primary phone number',
        ];
        return [
            'rules' => $rules,
            'messages' => $messages
        ];
    }
}
