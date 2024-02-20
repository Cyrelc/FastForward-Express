<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

use App\Models\Employee;
use App\Rules\PrimaryEmailConflict;

class StoreEmployeeRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        if(!empty($this->employee_id)) {
            $employee = Employee::findOrFail($this->employee_id);
            if(!$employee)
                return false;
            if(Auth::user()->can('updateBasic', $employee))
                return true;
        } else {
            if(Auth::user()->can('create', Employee::class))
                return true;
        }
        return false;
    }

    public function messages(): array {
        $messages =  [
            'updated_at.date_equals' => 'This employee has been modified since you loaded the page. Please re-load the employee and try again'
        ];

        $contactRequest = new StoreContactRequest();

        $messages = array_merge($messages, $contactRequest->messages());
        $employee = $this->employee_id ? Employee::findOrFail($this->employee_id) : null;

        if($employee ? Auth::user()->can('updateAdvanced', $employee) : true) {
            $messages = array_merge($messages, [
                    'sin.regex' => 'SIN must be in the format "### ### ###"',
                    'start_date.required' => 'Start Date is required.',
                    'start_date.date' => 'Start Date must be a date.',
                    'dob.required' => 'Date of Birth is required.',
                    'dob.date' => 'Date of Birth must be a date.',
                    'employee_number.required' => 'Please provide an employee number',
                    'employee_numer.unique' => 'Employee number is taken. Please choose a unique employee number'
                ]);

            if($this->is_driver) {
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

        return $messages;
    }

    protected function prepareForValidation() {
        $this->merge([
            'dob' => $this->birth_date,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        $employee = $this->employee_id ? Employee::findOrFail($this->employee_id) : null;
        $rules = [
            'updated_at' => 'exclude_if:employee_id,null|date|date_equals:' . ($employee ? $employee->updated_at : ''),
        ];

        $contactRequest = new StoreContactRequest();

        $rules = array_merge($rules, $contactRequest->rules(true));
        if($employee)
            $rules = array_merge($rules, [
                'contact.email_addresses' => array_merge($rules['contact.email_addresses'], [new PrimaryEmailConflict($employee->user_id)]),
            ]);

        if($employee ? Auth::user()->can('updateAdvanced', $employee) : true) {
            $rules = array_merge($rules, [
                'dob' => 'required|date',
                'employee_id' => 'sometimes|exists:employees,employee_id',
                'employee_number' => 'required|unique:employees,employee_number,' . $this->employee_id . ',employee_id',
                'is_enabled' => 'required|boolean',
                'sin' => ['nullable', 'sometimes', 'regex:/[0-9]{3} [0-9]{3} [0-9]{3}/'],
                'start_date' => 'required|date',
                'company_name' => 'sometimes|nullable',
                'permissions' => 'required|array',
            ]);

            if($this->is_driver) {
                $rules = array_merge($rules, [
                    'pickup_commission' => 'required|numeric',
                    'delivery_commission' => 'required|numeric',
                    'drivers_license_number' => 'required',
                    'drivers_license_expiration_date' => 'required|date',
                    'license_plate_number' => 'required',
                    'license_plate_expiration_date' => 'required|date',
                    'insurance_number' => 'required',
                    'insurance_expiration_date' => 'required|date',
                    'vehicle_type' => 'sometimes'
                ]);

                foreach(Employee::$permissionsMap as $key => $value) {
                    $rules['permissions.' . $value] = 'required';
                }
            }
        }

        return $rules;
    }
}
