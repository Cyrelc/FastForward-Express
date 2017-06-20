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


    public function Edit($driver) {
        $old = GetById($driver['driver_id']);

        //TODO: Fields

        $old->save();
    }
}
