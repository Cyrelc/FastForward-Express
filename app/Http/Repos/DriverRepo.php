<?php
namespace App\Http\Repos;

use App\Driver;

class DriverRepo {

    public function ListAll() {
        $drivers = Driver::All();

        return $drivers;
    }

    public function GetById($id) {
        $driver = Contact::where('driver_id', '=', $id)->first();

        return $driver;
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
