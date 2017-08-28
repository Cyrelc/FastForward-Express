<?php
namespace App\Http\Repos;

use App\Driver;
use App\DriverCommission;
use App\DriverEmergencyContact;

class DriverRepo {

    public function ListAll() {
        $drivers = Driver::All();

        return $drivers;
    }

    public function GetById($id) {
        $driver = Driver::where('driver_id', '=', $id)->first();

        return $driver;
    }

    public function Insert($driver, $emergencyContactIds) {
        $new = new Driver;
        $new = $new->create($driver);

        foreach($emergencyContactIds as $id)
            $new->contacts()->attach($id);

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

    public function GetCommissionByAccount($accountId) {
        $commission = DriverCommission::where('account_id', '=', $accountId)->first();

        return $commission;
    }

    public function ListEmergencyContacts($driverId) {
        $eContacts = DriverEmergencyContact::where('driver_id', '=', $driverId)->get();

        return $eContacts;
    }

    public function AddEmergencyContact($driverId, $contactId) {
        $driver = $this->GetById($driverId);
        $driver->contacts()->attach($contactId);
    }

    public function ChangePrimary($driverId, $contactId) {
        //Manually do this cause Laravel sucks, ensure parameters are valid
        if ($driverId == null || !is_numeric($driverId) || $driverId <= 0 || $contactId == null || !is_numeric($contactId) || $contactId <= 0) return;
        \DB::update('update driver_emergency_contacts set is_primary = 0 where driver_id = ' . $driverId . ' and is_primary = 1;');
        \DB::update('update driver_emergency_contacts set is_primary = 1 where driver_id = ' . $driverId . ' and contact_id = ' . $contactId . ';');
    }
}
