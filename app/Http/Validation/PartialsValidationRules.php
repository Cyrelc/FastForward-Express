<?php
namespace app\Http\Validation;

class PartialsValidationRules {
    public function GetAddressMinValidationRules($req, $prefix, $prefixFriendlyName) {
        return [
            'rules' => [
                $prefix . '_formatted' => 'required',
                $prefix . '_name' => 'required',
                $prefix . '_lat' => 'required|numeric',
                $prefix . '_lng' => 'required|numeric',
            ],
            'messages' => [
                $prefix . '_formatted.required' => $prefixFriendlyName . ' formatted address is required',
                $prefix . '_name.required' => $prefixFriendlyName . ' address name is required',
                $prefix . '_lat.required' => $prefixFriendlyName . ' requires a Latitude. If you are unable to find an address via search, please select the "Manual" button, and click as close as you can to your pickup location',
                $prefix . '_lng.required' => $prefixFriendlyName . ' requires a Longitude. If you are unable to find an address via search, please select the "Manual" button, and click as close as you can to your pickup location'
            ]
        ];
    }

    public function GetContactValidationRules($req, $userId = null) {
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email_addresses' => 'required',
            'phone_numbers' => 'required',
            'email_addresses.*.email' => 'required|email',
            'email_addresses.*.is_primary' => 'required',
            'phone_numbers.*.phone_number' => ['required','regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'phone_numbers.*.is_primary' => 'required',
            'phone_numbers.*.type' => 'required'
        ];
        $messages = [
            'first_name.required' => 'User first name field can not be empty',
            'last_name.required' => 'User last name field can not be empty',
            'phone_numbers.*.type' => 'Phone number type is a required field'
        ];
        if($userId) {
            foreach($req->email_addresses as $key => $email) {
                $existingUser = \App\User::where(['email' => $email['email']])->first();
                if($existingUser) {
                    $accountsList = [];
                    foreach($existingUser->accountUsers as $accountUser) {
                        $accountsList[] = $accountUser->account_id;
                    }
                    $rules = array_merge($rules, ['email_addresses.' . $key . '.email' => 'unique:users,email,' . $userId . ',user_id']);
                    $messages = array_merge($messages, ['email_addresses.' . $key . '.email.unique' => 'Requested email address is being used for login on account ' . implode(',', $accountsList) . '. Please select another.']);
                }
            }
        }

        if(isset($req->address_formatted)) {
            $rules = array_merge($rules, [
                'address_formatted' => 'required',
                'address_lat' => 'required|numeric|not_in:0',
                'address_lng' => 'required|numeric|not_in:0',
                'address_place_id' => 'required',
                'address_name' => 'required'
            ]);
        }

        return [
            'rules' => $rules,
            'messages' => $messages
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
