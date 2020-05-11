<?php
namespace App\Http\Repos;

use DB;
use App\Employee;
use App\EmployeeCommission;
use App\EmployeeEmergencyContact;

class EmployeeRepo {

    public function ListAll() {
        $employees = Employee::leftjoin('drivers', 'employees.employee_id', '=', 'drivers.employee_id')
                            ->leftjoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
                            ->leftjoin('phone_numbers', function($leftJoin) {
                                $leftJoin->on('phone_numbers.contact_id', '=', 'contacts.contact_id');
                                $leftJoin->where('phone_numbers.is_primary', '=', true);
                            })
                            ->select(
                                'employees.employee_id',
                                'employee_number',
                                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as employee_name'),
                                'phone_number as primary_phone',
                                'company_name',
                                'active',
                                'user_id'
                            );

        return $employees->get();
    }

    public function ListAllDrivers($filterActive = false) {
        $driverRepo = new DriverRepo();

        $drivers = $driverRepo->ListAll();
        $employeesWhoAreDrivers = [];
        foreach($drivers as $driver) {
            $employee = $this->getById($driver->employee_id);
            if(!$filterActive || $filterActive && $employee->active)
                array_push($employeesWhoAreDrivers, $employee);
        }
        return $employeesWhoAreDrivers;
    }

    public function ListAllActive() {
        $activeEmployees = Employee::where('active', true)->get();

        return $activeEmployees;
    }

    public function GetById($id) {
        $employee = Employee::where('employee_id', '=', $id)->first();

        return $employee;
    }

    public function GetEmployeeRelevantIds($employee_id) {
        $relevantIds['contact_ids'] = EmployeeEmergencyContact::where('employee_id', $employee_id)
            ->pluck('contact_id')
            ->toArray();
        array_push($relevantIds['contact_ids'], $this->GetById($employee_id)->contact_id);
        $relevantIds['email_ids'] = \App\EmailAddress::whereIn('contact_id', $relevantIds['contact_ids'])
            ->pluck('email_address_id')->toArray();
        $relevantIds['phone_ids'] = \App\PhoneNumber::whereIn('contact_id', $relevantIds['contact_ids'])
            ->pluck('phone_number_id')->toArray();
        $relevantIds['address_ids'] = \App\Address::whereIn('contact_id', $relevantIds)
            ->pluck('address_id')->toArray();

        return $relevantIds;
    }

    public function Insert($employee) {
        $new = new Employee;
        $new = $new->create($employee);

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
        $emergency_contacts = EmployeeEmergencyContact::where('employee_id', '=', $employeeId)
            ->leftJoin('contacts', 'employee_emergency_contacts.contact_id', '=', 'contacts.contact_id')
            ->leftJoin('phone_numbers', function($join){
                $join->on('phone_numbers.contact_id', '=', 'employee_emergency_contacts.contact_id');
                $join->on('phone_numbers.is_primary', '=', DB::raw(true));
            })
            ->leftJoin('email_addresses', function($join){
                $join->on('email_addresses.contact_id', '=', 'employee_emergency_contacts.contact_id');
                $join->on('email_addresses.is_primary', '=', DB::raw(true));
            })
            ->select(
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as name'),
                'email_addresses.email as primary_email',
                'phone_numbers.phone_number as primary_phone',
                'contacts.position',
                'employee_emergency_contacts.contact_id'
            );

        return $emergency_contacts->get();
    }

    public function AddEmergencyContact($emergency_contact) {
        $new = new EmployeeEmergencyContact;
        $new = $new->create($emergency_contact);
        return $new;
    }

    public function ChangePrimary($employeeId, $contactId) {
        //Manually do this cause Laravel sucks, ensure parameters are valid
        if ($employeeId == null || !is_numeric($employeeId) || $employeeId <= 0 || $contactId == null || !is_numeric($contactId) || $contactId <= 0) return;
        \DB::update('update employee_emergency_contacts set is_primary = 0 where employee_id = ' . $employeeId . ' and is_primary = 1;');
        \DB::update('update employee_emergency_contacts set is_primary = 1 where employee_id = ' . $employeeId . ' and contact_id = ' . $contactId . ';');
    }
}
