<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeListResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'company_name' => $this->company_name,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->contact->displayName(),
            'employee_number' => $this->employee_number,
            'is_enabled' => $this->is_enabled,
            'primary_email' => $this->contact->primary_email->email,
            'primary_phone' => $this->contact->primary_phone->phone_number,
            'user_id' => $this->user_id
        ];
    }
}
