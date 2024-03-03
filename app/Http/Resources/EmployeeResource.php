<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class EmployeeResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return array_merge($this->basicFields(), $this->advancedFields(), $this->driverFields());
    }

    private function basicFields() {
        $basicFields = [
            'contact' => new ContactResource($this->contact),
            'employee_id' => $this->employee_id,
            'is_driver' => $this->is_driver,
            'is_enabled' => $this->user->is_enabled,
            'permissions' => $this->getPermissions(),
            'updated_at' => $this->updated_at,
        ];

        unset($basicFields['contact']['email_types']);
        return $basicFields;
    }

    private function advancedFields() {
        if(Auth::user()->can('viewAdvanced', $this->resource))
            return [
                'activity_log' => $this->activity_log,
                'dob' => $this->dob,
                'employee_number' => $this->employee_number,
                'employee_permissions' => $this->permissions(),
                'sin' => $this->sin,
                'start_date' => $this->start_date,
            ];
        return [];
    }

    private function driverFields() {
        if(Auth::user()->can('viewAdvanced', $this->resource))
            return [
                'company_name' => $this->company_name,
                'delivery_commission' => $this->delivery_commission,
                'drivers_license_expiration_date' => $this->drivers_license_expiration_date,
                'drivers_license_number' => $this->drivers_license_number,
                'insurance_expiration_date' => $this->insurance_expiration_date,
                'insurance_number' => $this->insurance_number,
                'license_plate_expiration_date' => $this->license_plate_expiration_date,
                'license_plate_number' => $this->license_plate_number,
                'pickup_commission' => $this->pickup_commission,
                'vehicle_type_id' => $this->vehicle_type_id,
                'vehicle_types' => $this->vehicleSelections(),
            ];
        else if($this->is_driver)
            return [
                'drivers_license_expiration_date' => $this->drivers_license_expiration_date,
                'insurance_expiration_date' => $this->insurance_expiration_date,
                'license_plate_expiration_date' => $this->license_plate_expiration_date,
            ];
        return [];
    }

    // calculates and retrieves the list of actions the currently authenticated user is
    // allowed to perform on the model for the front end to use
    private function getPermissions() {
        $user = Auth::user();
        return [
            'viewBasic' => $user->can('view', $this->resource),
            'viewAdvanced' => $user->can('viewAdvanced', $this->resource),
            'editBasic' => $user->can('updateBasic', $this->resource),
            'editAdvanced' => $user->can('updateAdvanced', $this->resource),
            'viewActivityLog' => $user->can('viewActivityLog', $this->resource)
        ];
    }
}
