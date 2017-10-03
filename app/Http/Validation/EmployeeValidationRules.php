<?php
namespace app\Http\Validation;


class EmployeeValidationRules {
    public function GetValidationRules($req) {
        return [
            'rules' =>[
                'SIN' => ['regex:/[0-9]{3} [0-9]{3} [0-9]{3}/'],
                'startdate' => 'required|date',
                'DOB' => 'required|date',
                'employee_number' => 'required|unique:employees,employee_number,' . $req->employee_id . ',employee_id'
            ],
            'messages' => [
                'SIN.regex' => 'SIN must be in the format "### ### ###"',
                'startdate.required' => 'Start Date is required.',
                'startdate.date' => 'Start Date must be a date.',
                'DOB.required' => 'Date of Birth is required.',
                'DOB.date' => 'Date of Birth must be a date.',
                'employee_number.required' => 'Please provide an employee number',
                'employee_numer.unique' => 'Employee number is taken. Please choose a unique employee number'
            ]
        ];
    }
}
