<?php
namespace App\Http\Repos;

use DB;
use App\Employee;
use App\EmployeeCommission;
use App\EmployeeEmergencyContact;
use App\Http\Filters\IsNull;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class EmployeeRepo {
    public function ListAll() {
        $employees = Employee::leftjoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->leftjoin('phone_numbers', function($leftJoin) {
                $leftJoin->on('phone_numbers.contact_id', '=', 'contacts.contact_id');
                $leftJoin->where('phone_numbers.is_primary', '=', true);
            })
            ->leftjoin('email_addresses', function($leftJoin) {
                $leftJoin->on('email_addresses.contact_id', '=', 'contacts.contact_id');
                $leftJoin->where('email_addresses.is_primary', '=', true);
            })
            ->select(
                'email as primary_email',
                'employees.employee_id',
                'employee_number',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as employee_name'),
                'phone_number as primary_phone',
                'company_name',
                'active',
                'user_id'
            );

            $filteredEmployees = QueryBuilder::for($employees)
                ->allowedFilters([
                    AllowedFilter::exact('active', 'employees.active')
                ]);

        return $filteredEmployees->get();
    }

    public function ListAllDrivers($active = true) {
        $drivers = Employee::where('is_driver', 1)
            ->where('active', $active);

        return $drivers->get();
    }

    public function ListAllActive() {
        $activeEmployees = Employee::where('active', true)->get();

        return $activeEmployees;
    }

    public function GetActiveDriversWithContact() {
        $employees = Employee::leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
        ->where('is_driver', true)
        ->where('active', true);

        return $employees->get();
    }

    public function GetById($id) {
        $employee = Employee::where('employee_id', '=', $id)->first();

        return $employee;
    }

    public function GetEmployeeIdByEmployeeNumber($employeeNumber) {
        $employee = Employee::where('employee_number', $employeeNumber)->first();

        return $employee->employee_id;
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

    public function GetEmployeesList() {
        $employees = Employee::leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->select(
                DB::raw('concat(employee_number, " - ", first_name, " ", last_name) as label'),
                'employee_id as value'
            );

        return $employees->get();
    }

    public function GetEmployeeBirthdays() {
        $employees = Employee::leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
        ->where('active', true)
        ->whereMonth('dob', date('m'))
        ->select(
            DB::raw('concat(first_name, " ", last_name) as employee_name'),
            DB::raw("date_format(dob, '%M %D') as birthday")
        );

        return $employees->get();
    }

    public function GetEmployeesWithExpiries($date) {
        $employees = Employee::leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
        ->where('active', 1)
        ->where('is_driver', 1)
        ->where(function($query) use ($date) {
            $query->where('drivers_license_expiration_date', '<', $date)
            ->orWhere('license_plate_expiration_date', '<', $date)
            ->orWhere('insurance_expiration_date', '<', $date);
        })
        ->select(
            'drivers_license_expiration_date',
            'license_plate_expiration_date',
            'insurance_expiration_date',
            DB::raw('concat (first_name, " ", last_name) as employee_name'),
            'employee_id'
        );

        return $employees->get();
    }

    public function Insert($employee) {
        $new = new Employee;

        return $new->create($employee);
    }

    public function Update($employee) {
        $old = $this->GetById($employee['employee_id']);
        $fields = ['employee_number', 'sin', 'dob', 'active', 'is_driver'];
        $driverFields = [
            'license_plate_number',
            'license_plate_expiration_date',
            'drivers_license_number',
            'drivers_license_expiration_date',
            'insurance_number',
            'insurance_expiration_date',
            'pickup_commission',
            'delivery_commission',
            'company_name'
        ];
        foreach($fields as $field)
            $old->$field = $employee[$field];
        if($employee['is_driver'])
            foreach($driverFields as $field)
                $old->$field = $employee[$field];

        $old->save();
    }

    public function GetCommissionByAccount($accountId) {
        $commission = EmployeeCommission::where('account_id', '=', $accountId)->first();

        return $commission;
    }

    public function GetEmergencyContacts($employeeId) {
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
                'employee_emergency_contacts.is_primary',
                'employee_emergency_contacts.contact_id'
            );

        return $emergency_contacts->get();
    }

    public function AddEmergencyContact($emergencyContact) {
        $new = new EmployeeEmergencyContact;

        return $new->create($emergencyContact);
    }

    public function ToggleActive($employeeId) {
        $employee = Employee::where('employee_id', $employeeId)->first();

        $employee->active = !($employee->active);

        $employee->save();
    }

    public function GetDriverList($activeOnly = true) {
        $drivers = Employee::where('is_driver', 1)
            ->leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->when($activeOnly, function($query) {
                return $query->where('active', 1);
            })
            ->select(
                DB::raw('concat(employee_number, " - ", first_name, " ", last_name) as label'),
                'employee_id as value'
            );

        return $drivers->get();
    }
}
