<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyContactListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'contact_id' => $this->contact_id,
            'is_primary' => $this->is_primary,
            'name' => $this->contact->displayName(),
            'position' => $this->contact->position,
            'primary_email' => $this->contact->primary_email->email,
            'primary_phone' => $this->contact->primary_phone->phone_number,
        ];
    }
}
