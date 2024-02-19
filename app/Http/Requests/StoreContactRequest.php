<?php

namespace App\Http\Requests;

use App\Rules\SinglePrimary;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return false;
        // no authorize logic atm, since this is never called directly
        // $contact = Contact::find($req->contact_id);
        // return Auth::user()->can('update', $contact);
    }

    public function messages(): array {
        return [
            'contact.first_name.required' => 'User first name field can not be empty',
            'contact.last_name.required' => 'User last name field can not be empty',
            'contact.phone_numbers.*.type.required' => 'Phone number type is a required field'
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules($withAddress = false): array {
        $rules = [
            'contact.email_addresses' => ['required', 'array', new SinglePrimary],
            'contact.email_addresses.*.email_address_id' => 'sometimes',
            'contact.email_addresses.*.email' => 'required|email',
            'contact.email_addresses.*.is_primary' => 'required',
            'contact.email_addresses.*.type' => 'sometimes|array|nullable',
            'contact.email_addresses.*.delete' => 'sometimes',
            'contact.first_name' => 'required',
            'contact.last_name' => 'required',
            'contact.phone_numbers' => ['required', 'array', new SinglePrimary],
            'contact.phone_numbers.*.phone_number' => ['required','regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            // 'contact.phone_numbers.*.phone_number_id' => 'required|nullable',
            'contact.phone_numbers.*.is_primary' => 'required',
            'contact.phone_numbers.*.type' => 'required',
            'contact.phone_numbers.*.delete' => 'sometimes',
            'contact.position' => 'nullable|sometimes',
            'contact.preferred_name' => 'nullable|string|max:255',
            'contact.pronouns' => 'nullable|array'
        ];

        if($withAddress) {
            $addressRequest = new StoreAddressRequest();
            $addressRules = $addressRequest->rules();
            foreach($addressRules as $key => $rule) {
                $addressRules['contact.' . $key] = $rule;
                unset($addressRules[$key]);
            }
            $rules = array_merge($rules, $addressRules);
        }

        return $rules;
    }
}
