<?php

namespace App\Http\Collectors;
use \App\Http\Validation\Utils;

class EmployeeCollector {
    public function Collect($req, $contactId, $userId, $permissions) {
        $employee = [
            'contact_id' => $contactId,
            'employee_id' => $req->employee_id,
            'user_id' => $userId,
        ];

        if($permissions['editAdvanced']) {
            $employee['employee_number'] = $req->employee_number;
            $employee['start_date'] = (new \DateTime($req->input('start_date')))->format('Y-m-d');
            $employee['sin'] = $req->sin;
            $employee['dob'] = (new \DateTime($req->input('birth_date')))->format('Y-m-d');
            $employee['is_driver'] = filter_var($req->is_driver, FILTER_VALIDATE_BOOLEAN);

            if(filter_var($req->is_driver, FILTER_VALIDATE_BOOLEAN)) {
                $employee['drivers_license_number'] = $req->drivers_license_number;
                $employee['drivers_license_expiration_date'] = (new \DateTime($req->drivers_license_expiration_date))->format('Y-m-d');
                $employee['license_plate_number'] = $req->license_plate_number;
                $employee['license_plate_expiration_date'] = (new \DateTime($req->license_plate_expiration_date))->format('Y-m-d');
                $employee['insurance_number'] = $req->insurance_number;
                $employee['insurance_expiration_date'] = (new \DateTime($req->insurance_expiration_date))->format('Y-m-d');
                $employee['company_name'] = $req->company_name;
                $employee['pickup_commission'] = $req->pickup_commission;
                $employee['delivery_commission'] = $req->delivery_commission;
            }
        }

        return $employee;
    }

    public function CollectEmergencyContact($req, $contact_id, $is_primary = false) {
        $employeeRepo = new \App\Http\Repos\EmployeeRepo();
        $emergencyContacts = $employeeRepo->GetEmergencyContacts($req->employee_id);
        return [
            'employee_id' => $req->employee_id,
            'contact_id' => $contact_id,
            'is_primary' => $emergencyContacts ? $is_primary : true
        ];
    }
}

