<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address.formatted' => 'required',
            'address.lat' => 'required|numeric|not_in:0',
            'address.lng' => 'required|numeric|not_in:0',
            'address.name' => 'required',
            'address.place_id' => 'required',
        ];
    }
}
