<?php

namespace App\Http\Models\Chargeback;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Chargeback;

class ChargebackModelFactory {
    public function GetCreateModel() {
        $model = new ChargebackFormModel();
        $employeeRepo = new Repos\EmployeeRepo();
        $contactRepo = new Repos\ContactRepo();

        $model->employees = $employeeRepo->ListAllActive();
        foreach($model->employees as $employee) {
            $employee->contact = $contactRepo->GetById($employee->contact_id);
        }

        $model->date = date("U");

        return $model;
    }
}
?>
