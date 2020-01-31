<?php
namespace app\Http\Validation;


class DriverValidationRules {
    public function GetValidationRules() {
        //legacy license plate regex (for documentation purposes only/no longer enforced): 'regex:/([A-Z]{3}-[0-9]{4})|([B-WY][A-Z]{2}-[0-9]{3})|([1-9]-[0-9]{5})|([B-DF-HJ-NP-TV-XZ]-[0-9]{5})|([0-9]{2}-[A-Z][0-9]{3})/'
        return [
            'rules' =>[
                'pager_number' => ['regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
                'DLN' => 'required',
                'license_plate' => ['required', 'string', 'between:1,8'],
                'insurance' => 'required',
                'license_expiration' => 'required|date',
                'license_plate_expiration' => 'required|date',
                'insurance_expiration' => 'required|date',
                'pickup-commission' => 'required|numeric',
                'delivery-commission' => 'required|numeric'
            ],
            'messages' => [
                'DLN.required' => 'Driver License Number is required.',
                'license_plate.required' => 'License Plate is required.',
                'license_plate.regex' => 'License Plate must be in the format "AAA-####", "AAA-###", "#-#####", "A-#####", or "##-A###"',
                'insurance.required' => 'Insurance Number is required.',
                'license_expiration.required' => 'Drivers License Expiration Date is required.',
                'license_expiration.date' => 'Drivers License Expiration Date must be a date.',
                'license_plate_expiration.required' => 'License Plate Expiration Date is required.',
                'license_plate_expiration.date' => 'License Plate Expiration Date must be a date.',
                'insurance_expiration.required' => 'Insurance Expiration Date is required.',
                'insurance_expiration.date' => 'Insurance Expiration Date must be a date.',
                'pickup-commission.required' => 'Pickup Commission is required.',
                'pickup-commission.numeric' => 'Pickup Commission must be a number.',
                'delivery-commission.required' => 'Pickup Commission is required.',
                'delivery-commission.numeric' => 'Pickup Commission must be a number.',
            ]
        ];
    }
}
