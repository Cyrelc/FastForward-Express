<?php

namespace App\Http\Collectors;
use \App\Http\Validation\Utils;

class EmployeeCollector {
    public function Collect($req, $contactId, $userId) {
        return [
            'employee_id' => $req->input('employee_id'),
            'contact_id' => $contactId,
            'user_id' => $userId,
            'employee_number' => $req->employee_number,
            'stripe_id' => null,
            'start_date' => (new \DateTime($req->input('startdate')))->format('Y-m-d'),
            'sin' => $req->input('SIN'),
            'dob' => (new \DateTime($req->input('DOB')))->format('Y-m-d'),
            'active' => true
        ];
    }
    public function CollectEmergencyContact($req, $contact_id, $is_primary = false) {
        return [
            'employee_id' => $req->employee_id,
            'contact_id' => $contact_id,
            'is_primary' => $is_primary
        ];
    }
}

