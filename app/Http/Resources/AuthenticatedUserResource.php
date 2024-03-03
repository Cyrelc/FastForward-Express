<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AuthenticatedUserResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $permissionModelFactory = new \App\Http\Models\Permission\PermissionModelFactory();

        return [
            'user_id' => $this->id,
            'account_users' => $this->account_users,
            'contact' => new ContactResource($this->contact),
            'employee' => isset($this->employee) ? [
                'employee_id' => $this->employee->employee_id
            ] : [],
            'front_end_permissions' => $permissionModelFactory->getFrontEndPermissionsForUser($this),
            'is_impersonating' => session('original_user_id') != null,
            'user_settings' => $this->settings,
        ];
    }
}
