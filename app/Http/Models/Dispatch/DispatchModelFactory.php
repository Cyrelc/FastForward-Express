<?php
namespace App\Http\Models\Dispatch;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Dispatch;

class DispatchModelFactory {
    public function GetDrivers() {
        $model = new DispatchDriversFormModel();

        $driverRepo = new Repos\DriverRepo();
        $billRepo = new Repos\BillRepo();

        $model->drivers = $driverRepo->ListAllWithEmployeeAndContact();
        $model->newBills = $billRepo->GetBillsWithNoDriver();
        foreach($model->drivers as $driver)
            $driver->bills_on_board = $billRepo->GetBillsOnBoard($driver->driver_id);

        return $model;
    }
}
?>
