<?php

namespace App\Http\Resources;

use App\Models\EmailAddress;
use App\Models\PhoneNumber;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CreateEmployeeResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'contact' => [
                'phone_types' => (new PhoneNumber())->typeSelections(),
            ],
            'employee_permissions' => $this->resource->permissions(),
            'permissions' => $this->getPermissions(),
            'vehicle_types' => $this->resource->vehicleSelections(),
        ];
    }

    // calculates and retrieves the list of actions the currently authenticated user is
    // allowed to perform on the model for the front end to use
    private function getPermissions() {
        return [
            'create' => Auth::user()->can('create', $this->resource),
            'viewAdvanced' => Auth::user()->can('viewAdvanced', $this->resource),
            'editAdvanced' => Auth::user()->can('updateAdvanced', $this->resource)
        ];
    }
}
