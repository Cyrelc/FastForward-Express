<?php

namespace App\Http\Collectors;
use \App\Http\Validation\Utils;

class EmployeeCollector {
    public function Collect($req, $contactId, $userId) {
        $employee = [
            'employee_id' => $req->employee_id,
            'contact_id' => $contactId,
            'user_id' => $userId,
            'employee_number' => $req->employee_number,
            'start_date' => (new \DateTime($req->input('start_date')))->format('Y-m-d'),
            'sin' => $req->sin,
            'dob' => (new \DateTime($req->input('birth_date')))->format('Y-m-d'),
            'active' => filter_var($req->active, FILTER_VALIDATE_BOOLEAN),
            'is_driver' => filter_var($req->is_driver, FILTER_VALIDATE_BOOLEAN)
        ];
        if(filter_var($req->is_driver, FILTER_VALIDATE_BOOLEAN))
            $employee = array_merge($employee, [
                'drivers_license_number' => $req->drivers_license_number,
                'drivers_license_expiration_date' => (new \DateTime($req->drivers_license_expiration_date))->format('Y-m-d'),
                'license_plate_number' => $req->license_plate_number,
                'license_plate_expiration_date' => (new \DateTime($req->license_plate_expiration_date))->format('Y-m-d'),
                'insurance_number' => $req->insurance_number,
                'insurance_expiration_date' => (new \DateTime($req->insurance_expiration_date))->format('Y-m-d'),
                'company_name' => $req->company_name,
                'pickup_commission' => $req->pickup_commission,
                'delivery_commission' => $req->delivery_commission
            ]);
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

