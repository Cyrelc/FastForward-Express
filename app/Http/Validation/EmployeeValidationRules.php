<?php
namespace app\Http\Validation;


class EmployeeValidationRules {
    public function GetValidationRules($req, $permissions, $oldEmployee) {
        $rules = [
            'updated_at' => 'exclude_if:employee_id,null|date|date_equals:' . ($oldEmployee ? $oldEmployee->updated_at : ''),
        ];
        $messages = [
            'updated_at.date_equals' => 'This employee has been modified since you loaded the page. Please re-load the employee and try again'
        ];

        if($permissions['editAdvanced']) {
            array_merge($rules, [
                'SIN' => ['regex:/[0-9]{3} [0-9]{3} [0-9]{3}/'],
                'start_date' => 'required|date',
                'birth_date' => 'required|date',
                'employee_number' => 'required|unique:employees,employee_number,' . $req->employee_id . ',employee_id',
            ]);

            array_merge($messages, [
                'SIN.regex' => 'SIN must be in the format "### ### ###"',
                'start_date.required' => 'Start Date is required.',
                'start_date.date' => 'Start Date must be a date.',
                'birth_date.required' => 'Date of Birth is required.',
                'birth_date.date' => 'Date of Birth must be a date.',
                'employee_number.required' => 'Please provide an employee number',
                'employee_numer.unique' => 'Employee number is taken. Please choose a unique employee number'
            ]);

            if($req->is_driver === 'true') {
                $rules = array_merge($rules, [
                    'pickup_commission' => 'required|numeric',
                    'delivery_commission' => 'required|numeric',
                    'drivers_license_number' => 'required',
                    'drivers_license_expiration_date' => 'required|date',
                    'license_plate_number' => 'required',
                    'license_plate_expiration_date' => 'required|date',
                    'insurance_number' => 'required',
                    'insurance_expiration_date' => 'required|date'
                ]);

                $messages = array_merge($messages, [
                    'drivers_license_number.required' => 'Drivers License Number is required.',
                    'license_plate_number.required' => 'License Plate is required.',
                    'insurance_number.required' => 'Insurance Number is required.',
                    'drivers_license_expiration_date.required' => 'Drivers License Expiration Date is required.',
                    'drivers_license_expiration_date.date' => 'Drivers License Expiration Date must be a date.',
                    'license_plate_expiration_date.required' => 'License Plate Expiration Date is required.',
                    'license_plate_expiration_date.date' => 'License Plate Expiration Date must be a date.',
                    'insurance_expiration_date.required' => 'Insurance Expiration Date is required.',
                    'insurance_expiration_date.date' => 'Insurance Expiration Date must be a date.',
                    'pickup_commission.required' => 'Pickup Commission is required.',
                    'pickup_commission.numeric' => 'Pickup Commission must be a number.',
                    'delivery_commission.required' => 'Pickup Commission is required.',
                    'delivery_commission.numeric' => 'Pickup Commission must be a number.',
                ]);
            }
        }

        return ['rules' => $rules, 'messages' => $messages];
    }

    //legacy license plate regex (for documentation purposes only/no longer enforced): 'regex:/([A-Z]{3}-[0-9]{4})|([B-WY][A-Z]{2}-[0-9]{3})|([1-9]-[0-9]{5})|([B-DF-HJ-NP-TV-XZ]-[0-9]{5})|([0-9]{2}-[A-Z][0-9]{3})/'
    //legacy phone number regex (for possible future re-use): 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'
}
