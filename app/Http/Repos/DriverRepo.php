<?php
namespace App\Http\Repos;

use App\Driver;
use App\DriverCommission;

class DriverRepo {

    public function ListAll() {
        $drivers = Driver::All();

        return $drivers;
    }

    public function GetById($id) {
        $driver = Driver::where('driver_id', '=', $id)->first();

        return $driver;
    }

    public function GetCommissionByAccount($accountId) {
        $commission = DriverCommission::where('account_id', '=', $accountId)->first();

        return $commission;
    }

    public function Insert($driver) {
        $new = new Driver;

        $new = $new->create($driver);

        return $new;
    }


    public function Update($driver) {
        $old = $this->GetById($driver['driver_id']);

        $old->driver_number = $driver['driver_number'];
        $old->drivers_license_number = $driver['drivers_license_number'];
        $old->license_expiration = $driver['license_expiration'];
        $old->license_plate_number = $driver['license_plate_number'];
        $old->license_plate_expiration = $driver['license_plate_expiration'];
        $old->insurance_number = $driver['insurance_number'];
        $old->insurance_expiration = $driver['insurance_expiration'];
        $old->sin = $driver['sin'];
        $old->dob = $driver['dob'];
        $old->active = $driver['active'];
        $old->pickup_commission = $driver['pickup_commission'];
        $old->delivery_commission = $driver['delivery_commission'];

        $old->save();
    }
}
