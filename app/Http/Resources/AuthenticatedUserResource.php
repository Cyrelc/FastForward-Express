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
            'account_users' => $this->accountUsers,
            'contact' => $this->contact ? new ContactResource($this->contact) : null,
            'employee' => isset($this->employee) ? [
                'employee_id' => $this->employee->employee_id
            ] : null,
            'front_end_permissions' => $permissionModelFactory->getFrontEndPermissionsForUser($this),
            'is_impersonating' => session('original_user_id') != null,
            'user_settings' => $this->settings,
            'time_zone_reference' => $this->timeZoneReference($request->query('time_zone')),
        ];
    }

    /**
     * The correct UTC offsets - per the server's timezone data, which is the authority - for the server's zone and the
     * browser's own zone, now and over the coming months. The browser compares them with what its own timezone data
     * says to detect out-of-date rules (e.g. Alberta's 2026 move to permanent UTC-6), wherever the user is located.
     */
    private function timeZoneReference($browserTimeZone) {
        $zones = [config('app.timezone')];
        try {
            if(is_string($browserTimeZone) && $browserTimeZone !== '')
                $zones[] = (new \DateTimeZone($browserTimeZone))->getName();
        } catch(\Exception $e) {
            // A zone the server doesn't know can't be checked - the server's zone still is
        }

        $now = time();
        $reference = [];
        foreach(array_unique($zones) as $zone) {
            $timeZone = new \DateTimeZone($zone);
            foreach([0, 3, 6, 9] as $months) {
                $timestamp = strtotime('+' . $months . ' months', $now);
                $reference[] = [
                    'time_zone' => $zone,
                    'timestamp' => $timestamp,
                    'offset' => $timeZone->getOffset(new \DateTime('@' . $timestamp)) / 60,
                ];
            }
        }

        return $reference;
    }
}
