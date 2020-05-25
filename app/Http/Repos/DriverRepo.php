<?php
namespace App\Http\Repos;

use DB;
use App\Driver;

class DriverRepo {
	public function ListAll() {
		$drivers = Driver::All();

		return $drivers;
	}

	public function ListAllWithEmployeeAndContact() {
		$drivers = Driver::leftJoin('employees', 'employees.employee_id', '=', 'drivers.employee_id')
				->leftJoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
				->select('driver_id',
						'employees.employee_id',
						'employees.active',
						'employees.employee_number',
						DB::raw('concat(contacts.first_name, " ", contacts.last_name) as employee_name'));

		return $drivers->get();
	}

	public function GetById($driverId) {
		$driver = Driver::where('driver_id', '=', $driverId)->first();

		return $driver;
	}

	public function GetContactByDriverId($driver_id) {
		$contact = Driver::join('employees', 'employees.employee_id', '=', 'drivers.employee_id')
			->join('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
			->where('driver_id', $driver_id)
			->select('first_name', 'last_name', 'contacts.contact_id', 'position')
			->first();

		return $contact;
	}

	public function Insert($driver) {
    	$new = new Driver;

    	return ($new->create($driver));
	}

	public function Update($driver) {
		$old = $this->GetById($driver['driver_id']);

		$fields = ['company_name', 'drivers_license_number','license_expiration', 'license_plate_number', 'license_plate_expiration', 'insurance_number', 'insurance_expiration', 'pickup_commission', 'delivery_commission'];

		foreach($fields as $field) {
			$old->$field = $driver[$field];
		}

		$old->save();

		return $old;
	}

	public function GetByEmployeeId($employee_id) {
		$driver = Driver::where('employee_id', '=', $employee_id)->first();

		return $driver;
	}

	public function DeleteByEmployeeId($employee_id) {
		$driver = Driver::where('employee_id', '=', $employee_id)->first();

		$driver->delete();
		return;
	}
}

?>
