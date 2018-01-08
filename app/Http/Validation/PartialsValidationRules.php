<?php
namespace app\Http\Validation;

class PartialsValidationRules {
    public function GetAddressValidationRules($prefix, $prefix_name) {
        return [
            'rules' => [
                $prefix . '-street' => 'required',
                $prefix . '-city' => 'required',
                //Regex used found here: http://regexlib.com/REDetails.aspx?regexp_id=417
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

    public function GetContactsValidationRules($req, $contacts, $validateAddress) {
        $validationRules = [];
        $validationMessages = [];
        $contact_count = 0;

        foreach($contacts as $contact) {
            //Skip validation for any deleted contacts, and don't increase the contact count
            if ($contact['action'] == 'delete')
                continue;

            $contact_count++;

            $contactValidationRules = $this->GetContactValidationRules($contact, $contact['first_name'] . ' ' . $contact['last_name']);
            $validationRules = array_merge($validationRules, $contactValidationRules['rules']);
            $validationMessages = array_merge($validationMessages, $contactValidationRules['messages']);

            if ($validateAddress) {
                $addrValidation = $this->GetAddressValidationRules($contact['prefix'] . '-address', 'Contact');
                $validationRules = array_merge($validationRules, $addrValidation['rules']);
                $validationMessages = array_merge($validationMessages, $addrValidation['messages']);
            }
        }

        if($contact_count == 0) {
            //Manually fail validation, by checking for a field that cannot exist, if there isn't at least one contact
            $validationRules[$contact['prefix'] . '-contacts-min'] = 'required';
            $validationMessages[$contact['prefix'] . '-contacts-min.required'] = 'There must be at least one provided contact. Please contact support';
        }

        return [
            'rules' => $validationRules,
            'messages' => $validationMessages,
        ];
    }

    public function GetContactValidationRules($contact, $prefix_name) {
        $phones_count = 0;

        $validation = [
            'rules' => [
                $contact['prefix'] . '-first-name' => 'required',
                $contact['prefix'] . '-last-name' => 'required',
                $contact['prefix'] . '-action' => 'required',
                //Regex used found here: http://www.regexlib.com/REDetails.aspx?regexp_id=607
                $contact['prefix'] . '-email1' => 'required|email',
                $contact['prefix'] . '-email2' => 'email'
            ],
            'messages' => [
                $contact['prefix'] . '-first-name.required' => $prefix_name . ' First Name is required.',
                $contact['prefix'] . '-last-name.required' => $prefix_name . ' Last Name is required.',
                $contact['prefix'] . '-action' => $prefix_name . ' action is required. Please contact support',
                $contact['prefix'] . '-email1.required' => $prefix_name . ' Primary Email is required.',
                $contact['prefix'] . '-email1.email' => $prefix_name . ' Primary Email must be an email.',
                $contact['prefix'] . '-email2.email' => $prefix_name . ' Secondary Email must be an email.',
            ]
        ];

        foreach($contact['phone_numbers'] as $phone) {
            if($phone['action'] == 'delete')
                continue;

            $phones_count++;
            $phoneValidation = $this->GetPhoneValidationRules($phone, $contact['first_name'] . ' ' . $contact['last_name']);
            $validation['rules'] = array_merge($validation['rules'], $phoneValidation['rules']);
            $validation['messages'] = array_merge($validation['messages'], $phoneValidation['messages']);
        }

        if($phones_count == 0) {
            //Manually fail validation, by checking for a field that cannot exist, if there isn't at least one phone number
            $validation['rules'][$contact['prefix'] . '-phone-number'] = 'required';
            $validation['messages'][$contact['prefix'] . '-phone-number.required'] = 'Please provide at least one phone number for ' . $contact['first_name'] . ' ' . $contact['last_name'];
        }

        return $validation;
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

    public function GetPhoneValidationRules($phone, $contact_name) {
        $validationRules = [
            $phone['prefix'] . '-number' => ['required','regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
        ];
        $validationMessages = [
            $phone['prefix'] . '-number.required' => $contact_name . ' Phone Number must not be blank. Please delete empty phone numbers if that is your intention',
            $phone['prefix'] . '-number.regex' => $contact_name . ' Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".'
        ];
        return [
            'rules' => $validationRules,
            'messages' => $validationMessages
        ];
    }
}
