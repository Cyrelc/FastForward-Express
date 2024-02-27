<?php
namespace App\Http\Repos;

use DB;
use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use App\Models\User;
use App\Http\Filters\IsNull;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Auth;

class EmployeeRepo {
    public function getActiveDriversWithContact() {
        $employees = Employee::leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
            ->leftJoin('users', 'users.id', '=', 'employees.user_id')
            ->select(
                'company_name',
                'employee_id',
                'employee_number',
                'first_name',
                'is_enabled',
                'last_name',
                'preferred_name'
            )->where('is_driver', true)
            ->where('is_enabled', true);

        return $employees->get();
    }

    public function getDriverList($activeOnly = true) {
        $drivers = Employee::where('is_driver', 1)
            ->leftJoin('users', 'users.id', '=', 'employees.user_id')
            ->leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->when($activeOnly, function($query) {
                return $query->where('users.is_enabled', 1);
            })->select(
                DB::raw('concat(employee_number, " - ", coalesce(preferred_name, concat(first_name, " ", last_name))) as label'),
                'employee_id as value',
                'pickup_commission',
                'delivery_commission',
                'employee_id',
                'is_enabled as active'
            );

        return $drivers->get();
    }

    public function getEmployeeBirthdays() {
        $employees = Employee::leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
        ->leftJoin('users', 'users.id', '=', 'employees.user_id')
        ->where('is_enabled', true)
        ->whereMonth('dob', date('m'))
        ->select(
            DB::raw('coalesce(preferred_name, concat(first_name, " ", last_name)) as employee_name'),
            DB::raw("date_format(dob, '%M %D') as birthday")
        );

        return $employees->get();
    }

    public function getEmployeesWithExpiries($date) {
        $employees = Employee::leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
            ->leftJoin('users', 'users.id', '=', 'employees.user_id')
            ->where('is_enabled', 1)
            ->where('is_driver', 1)
            ->where(function($query) use ($date) {
                $query->whereDate('drivers_license_expiration_date', '<', $date)
                ->orWhereDate('license_plate_expiration_date', '<', $date)
                ->orWhereDate('insurance_expiration_date', '<', $date);
            })
            ->select(
                'drivers_license_expiration_date',
                'license_plate_expiration_date',
                'insurance_expiration_date',
                DB::raw('coalesce(preferred_name, concat (first_name, " ", last_name)) as employee_name'),
                'employee_id'
            );

        return $employees->get();
    }

    public function getEmployeesWithUnmanifestedBillsBetweenDates($startDate, $endDate) {
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
}
