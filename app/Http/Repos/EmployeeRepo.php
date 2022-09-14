<?php
namespace App\Http\Repos;

use DB;
use App\Employee;
use App\EmployeeCommission;
use App\EmployeeEmergencyContact;
use App\User;
use App\Http\Filters\IsNull;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Auth;

class EmployeeRepo {
    public function AddEmergencyContact($emergencyContact) {
        $new = new EmployeeEmergencyContact;

        return $new->create($emergencyContact);
    }

    // public function GetCommissionByAccount($accountId) {
    //     $commission = EmployeeCommission::where('account_id', '=', $accountId)->first();

    //     return $commission;
    // }

    public function GetActiveDriversWithContact() {
        $employees = Employee::leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
            ->leftJoin('users', 'users.user_id', '=', 'employees.user_id')
            ->select(
                'company_name',
                'employee_id',
                'employee_number',
                'first_name',
                'is_enabled',
                'last_name'
            )->where('is_driver', true)
            ->where('is_enabled', true);

        return $employees->get();
    }

    public function GetById($employeeId, $permissions) {
        $employee = Employee::where('employee_id', '=', $employeeId)
            ->leftJoin('users', 'users.user_id', '=', 'employees.user_id');

        if($permissions)
            $employee->select(
                array_merge(
                    Employee::$readOnlyFields,
                    $permissions['viewAdvanced'] ? Employee::$advancedFields : [],
                    $permissions['viewAdvanced'] ? Employee::$driverFields : [],
                    ['users.is_enabled as is_enabled']
                )
            );

        return $employee->first();
    }

    public function GetDriverList($activeOnly = true) {
        $drivers = Employee::where('is_driver', 1)
            ->leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->leftJoin('users', 'users.user_id', '=', 'employees.user_id')
            ->when($activeOnly, function($query) {
                return $query->where('users.is_enabled', 1);
            })->select(
                DB::raw('concat(employee_number, " - ", first_name, " ", last_name) as label'),
                'employee_id as value',
                'pickup_commission',
                'delivery_commission',
                'employee_id',
                'is_enabled as active'
            );

        return $drivers->get();
    }

    public function GetEmergencyContactByContactId($contactId) {
        $emergencyContact = EmployeeEmergencyContact::where('contact_id', $contactId);

        return $emergencyContact->first();
    }

    public function GetEmergencyContacts($employeeId) {
        $emergency_contacts = EmployeeEmergencyContact::where('employee_id', '=', $employeeId)
            ->leftJoin('contacts', 'employee_emergency_contacts.contact_id', '=', 'contacts.contact_id')
            ->leftJoin('phone_numbers', function($join) {
                $join->on('phone_numbers.contact_id', '=', 'employee_emergency_contacts.contact_id');
                $join->on('phone_numbers.is_primary', '=', DB::raw(true));
            })
            ->leftJoin('email_addresses', function($join) {
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

    public function GetEmployeeBirthdays() {
        $employees = Employee::leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
        ->leftJoin('users', 'users.user_id', '=', 'employees.user_id')
        ->where('is_enabled', true)
        ->whereMonth('dob', date('m'))
        ->select(
            DB::raw('concat(first_name, " ", last_name) as employee_name'),
            DB::raw("date_format(dob, '%M %D') as birthday")
        );

        return $employees->get();
    }

    public function GetEmployeeIdByEmployeeNumber($employeeNumber) {
        $employee = Employee::where('employee_number', $employeeNumber)->first();

        return $employee->employee_id;
    }

    public function GetEmployeeRelevantIds($employee_id) {
        $relevantIds['contact_ids'] = EmployeeEmergencyContact::where('employee_id', $employee_id)
            ->pluck('contact_id')
            ->toArray();
        array_push($relevantIds['contact_ids'], $this->GetById($employee_id, null)->contact_id);
        $relevantIds['email_ids'] = \App\EmailAddress::whereIn('contact_id', $relevantIds['contact_ids'])
            ->pluck('email_address_id')->toArray();
        $relevantIds['phone_ids'] = \App\PhoneNumber::whereIn('contact_id', $relevantIds['contact_ids'])
            ->pluck('phone_number_id')->toArray();
        $relevantIds['address_ids'] = \App\Address::whereIn('contact_id', $relevantIds)
            ->pluck('address_id')->toArray();

        return $relevantIds;
    }

    public function GetEmployeesList($employeeId = null) {
        $employees = Employee::leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->select(
                DB::raw('concat(employee_number, " - ", first_name, " ", last_name) as label'),
                'employee_id as value'
            );

        if($employeeId)
            $employees->where('employee_id', $employeeId);

        return $employees->get();
    }

    public function GetEmployeesWithExpiries($date) {
        $employees = Employee::leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
        ->leftJoin('users', 'users.user_id', '=', 'employees.user_id')
        ->where('is_enabled', 1)
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

    public function GetEmployeesWithUnmanifestedBillsBetweenDates($startDate, $endDate) {
        $employees = Employee::leftJoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
            ->select(
                'employees.employee_id',
                'employee_number',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as label'),
                DB::raw('(
                    select count(distinct bills.bill_id)
                    from line_items left join charges on charges.charge_id = line_items.charge_id
                    left join bills on bills.bill_id = charges.bill_id
                    where (
                        (pickup_manifest_id is null and coalesce(line_items.pickup_driver_id, bills.pickup_driver_id) = employees.employee_id) or
                        (delivery_manifest_id is null and coalesce(line_items.delivery_driver_id, bills.delivery_driver_id) = employees.employee_id)) and
                        driver_amount != 0 and
                        percentage_complete = 100
                        and date(time_pickup_scheduled) between cast("' . $startDate . '" as date) and cast("' . $endDate . '" as date)
                    ) as valid_bill_count'
                ),
                DB::raw('(
                    select count(distinct bills.bill_id) from line_items
                        left join charges on charges.charge_id = line_items.charge_id
                        left join bills on bills.bill_id = charges.bill_id
                        where (
                            (pickup_manifest_id is null and coalesce(line_items.pickup_driver_id, bills.pickup_driver_id) = employees.employee_id)
                            or (delivery_manifest_id is null and coalesce(line_items.delivery_driver_id, bills.delivery_driver_id) = employees.employee_id)
                        )
                        and driver_amount != 0
                        and percentage_complete = 100
                        and date(time_pickup_scheduled) < cast("' . $startDate . '" as date)
                    ) as legacy_bill_count'
                ),
                DB::raw(
                    '(select count(distinct bills.bill_id) from line_items
                        left join charges on charges.charge_id = line_items.charge_id
                        left join bills on bills.bill_id = charges.bill_id
                            where (
                                (pickup_manifest_id is null and coalesce(line_items.pickup_driver_id, bills.pickup_driver_id) = employees.employee_id)
                                or (delivery_manifest_id is null and coalesce(line_items.delivery_driver_id, bills.delivery_driver_id) = employees.employee_id)
                            )
                            and driver_amount > 0
                            and percentage_complete < 100
                            and date(time_pickup_scheduled) between cast("' . $startDate . '" as date) and cast("' . $endDate . '" as date)
                        ) +
                        (select count(distinct bills.bill_id) from bills
                            where date(time_pickup_scheduled) between cast("' . $startDate . '" as date) and cast("' . $endDate . '" as date)
                            and bill_id not in (select bill_id from line_items left join charges on charges.charge_id = line_items.charge_id)
                            and (pickup_driver_id = employees.employee_id or delivery_driver_id = employees.employee_id)
                        )
                    as incomplete_bill_count'
                ),
            )->havingRaw('valid_bill_count > 0')
            ->orHavingRaw('legacy_bill_count > 0')
            ->orHavingRaw('incomplete_bill_count > 0');

        return $employees->get();
    }

    public function Insert($employee) {
        $new = new Employee;
        $new->created_at = date('Y-m-d H:i:s');
        $new->updated_at = date('Y-m-d H:i:s');

        return $new->create($employee);
    }

    public function ListAll($req) {
        $employees = Employee::leftjoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->leftJoin('users', 'users.user_id', '=', 'employees.user_id')
            ->leftjoin('phone_numbers', function($leftJoin) {
                $leftJoin->on('phone_numbers.contact_id', '=', 'contacts.contact_id');
                $leftJoin->where('phone_numbers.is_primary', '=', true);
            })
            ->leftjoin('email_addresses', function($leftJoin) {
                $leftJoin->on('email_addresses.contact_id', '=', 'contacts.contact_id');
                $leftJoin->where('email_addresses.is_primary', '=', true);
            })->select(
                'email_addresses.email as primary_email',
                'employees.employee_id',
                'employee_number',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as employee_name'),
                'phone_number as primary_phone',
                'company_name',
                'employees.user_id',
                'users.is_enabled as active'
            );

            $filteredEmployees = QueryBuilder::for($employees)
                ->allowedFilters([
                    AllowedFilter::exact('active', 'users.is_enabled')
                ]);

        return $filteredEmployees->get();
    }

    public function ListAllActive() {
        $activeEmployees = Employee::leftJoin('users', 'users.user_id', '=', 'employees.user_id')
            ->leftJoin('users'. 'users.user_id', '=', 'employees.user_id')
            ->where('is_enabled', true);

        return $activeEmployees->get();
    }

    public function ToggleActive($employeeId) {
        $employee = Employee::where('employee_id', $employeeId)->first();

        $user = User::where('user_id', $employee->user_id)->first();
        $user->is_enabled = !($user->is_enabled);

        $user->save();
    }

    public function Update($employee, $permissions) {
        $old = $this->GetById($employee['employee_id'], $permissions);

        if($permissions['editBasic'])
            foreach(Employee::$basicFields as $field)
                $old[$field] = $employee[$field];

        if($permissions['editAdvanced'])
            foreach(Employee::$advancedFields as $field)
                $old->$field = $employee[$field];

        if($permissions['editAdvanced'] && $employee['is_driver'])
            foreach(Employee::$driverFields as $field)
                $old->$field = $employee[$field];

        $old->updated_at = date('Y-m-d H:i:s');

        $old->save();
        return $old;
    }
}
