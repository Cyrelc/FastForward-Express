<?php

namespace App\Http\Models\Chargeback;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Chargeback;
use App\Models\Contact;

class ChargebackModelFactory {
    public function GetCreateModel() {
        $model = new ChargebackFormModel();
        $employeeRepo = new Repos\EmployeeRepo();

        $model->employees = $employeeRepo->listAll(true);
        foreach($model->employees as $employee) {
            $employee->contact = Contact::find($employee->contact_id);
        }

        $model->date = date("U");

        return $model;
    }

    public function GetEditModel() {
        $model = new ChargebackEditFormModel();
        $employeeRepo = new Repos\EmployeeRepo();
        $chargebackRepo = new Repos\ChargebackRepo();

        $employees = $employeeRepo->listAll(true);
        foreach($employees as $employee) {
            $chargebacks = $chargebackRepo->GetActiveByEmployeeId($employee->employee_id);
            if(count($chargebacks) > 0)
                array_push($model->employees, $employee);
        }

        foreach($model->employees as $employee) {
            $employee->contact = Contact::find($employee->contact_id);
            $employee->chargebacks = $chargebackRepo->GetActiveByEmployeeId($employee->employee_id);
        }

        return $model;
    }
}
?>
