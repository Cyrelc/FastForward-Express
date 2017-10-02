<?php
namespace App\Http\Repos;

use App\Employee;
use App\EmployeeCommission;
use App\EmployeeEmergencyContact;

class EmployeeRepo {

    public function ListAll() {
        $employees = Employee::All();

        return $employees;
    }

    public function ListAllDrivers() {
        $driverRepo = new DriverRepo();

        $drivers = $driverRepo->ListAll();
        $employeesWhoAreDrivers = [];
        foreach($drivers as $driver) {
            array_push($employeesWhoAreDrivers, $this->getById($driver->employee_id));
        }
        return $employeesWhoAreDrivers;
    }

    public function GetById($id) {
        $employee = Employee::where('employee_id', '=', $id)->first();

        return $employee;
    }

    public function Insert($employee, $emergencyContactIds) {
        $new = new Employee;
        $new = $new->create($employee);

        foreach($emergencyContactIds as $id)
            $new->contacts()->attach($id);

        return $new;
    }

    public function Update($employee) {
        $old = $this->GetById($employee['employee_id']);

        $old->employee_number = $employee['employee_number'];
        $old->sin = $employee['sin'];
        $old->dob = $employee['dob'];
        $old->active = $employee['active'];

        $old->save();
    }

    public function GetCommissionByAccount($accountId) {
        $commission = EmployeeCommission::where('account_id', '=', $accountId)->first();

        return $commission;
    }

    public function ListEmergencyContacts($employeeId) {
        $emergency_contacts = EmployeeEmergencyContact::where('employee_id', '=', $employeeId)->get();

        return $emergency_contacts;
    }

    public function AddEmergencyContact($employeeId, $contactId) {
        $employee = $this->GetById($employeeId);
        $employee->contacts()->attach($contactId);
    }

    public function ChangePrimary($employeeId, $contactId) {
        //Manually do this cause Laravel sucks, ensure parameters are valid
        if ($employeeId == null || !is_numeric($employeeId) || $employeeId <= 0 || $contactId == null || !is_numeric($contactId) || $contactId <= 0) return;
        \DB::update('update employee_emergency_contacts set is_primary = 0 where employee_id = ' . $employeeId . ' and is_primary = 1;');
        \DB::update('update employee_emergency_contacts set is_primary = 1 where employee_id = ' . $employeeId . ' and contact_id = ' . $contactId . ';');
    }
}
