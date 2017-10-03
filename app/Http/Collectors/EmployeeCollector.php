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

    public function Remerge($req, $employee) {
        if (Utils::HasValue($req->old('startdate')))
            $employee->start_date = strtotime($req->old('startdate'));

        if (Utils::HasValue($req->old('SIN')))
            $employee->sin = $req->old('SIN');

        if (Utils::HasValue($req->old('DOB')))
            $employee->dob = strtotime($req->old('DOB'));

        if (Utils::HasValue($req->old('employee_number')))
            $employee->employee_number = $req->old('employee_number');

        return $employee;
    }
}
