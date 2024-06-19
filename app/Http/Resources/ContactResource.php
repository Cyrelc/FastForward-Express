<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'address' => $this->address,
            'contact_id' => $this->contact_id,
            'display_name' => $this->displayName(),
            'email_addresses' => $this->email_addresses,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_numbers' => $this->phone_numbers,
            'position' => $this->position,
            'preferred_name' => $this->preferred_name,
            'pronouns' => $this->pronouns,
        ];
    }
}
