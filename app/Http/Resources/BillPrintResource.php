<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillPrintResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $charges = [];
        foreach($this->charges as $charge)
            $charges[] = [
                'account_id' => $charge->charge_account_id,
                'charge_account_name' => $charge->account ? $charge->account->name : $charge->paymentType->name,
                'charge_account_number' => $charge->account ? $charge->account->account_number : null,
                'price' => $charge->price,
                'line_items' => $charge->lineItems->map(function($lineItem) {
                    return [
                        'name' => $lineItem->name,
                        'friendly_type_name' => $lineItem->typeName(),
                        'price' => $lineItem->price
                    ];
                }),
            ];

        return [
            'bill_id' => $this->bill_id,
            'charges' => $charges,
            'delivery_address' => ['formatted' => $this->delivery_address->formatted, 'name' => $this->delivery_address->name],
            'delivery_driver_number' => $this->delivery_employee->employee_number ?? null,
            'delivery_type_friendly' => $this->delivery_type_name(),
            'description' => $this->description,
            'is_min_weight_size' => $this->is_min_weight_size,
            'packages' => $this->packages,
            'pickup_address' => ['formatted' => $this->pickup_address->formatted, 'name' => $this->pickup_address->name],
            'pickup_driver_number' => $this->pickup_employee->employee_number ?? null,
            'proof_of_delivery_required' => $this->proof_of_delivery_required,
            'time_delivery_scheduled' => $this->time_delivery_scheduled,
            'time_pickup_scheduled' => $this->time_pickup_scheduled,
            'use_imperial' => $this->use_imperial,
        ];
    }
}
