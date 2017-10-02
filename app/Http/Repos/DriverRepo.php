<?php
namespace App\Http\Repos;

use App\Driver;

class DriverRepo {
	public function ListAll() {
		$drivers = Driver::All();

		return $drivers;
	}

	public function GetById($driverId) {
		$driver = Driver::where('driver_id', '=', $driverId)->first();

		return $driver;
	}

	public function Insert($driver) {
    	$new = new Driver;

    	return ($new->create($driver));
	}

	public function Update($driver) {
		$old = $this->GetById($driver['driver_id']);

		$fields = ['drivers_license_number','license_expiration', 'license_plate_number', 'license_plate_expiration', 'insurance_number', 'insurance_expiration', 'pickup_commission', 'delivery_commission'];

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
