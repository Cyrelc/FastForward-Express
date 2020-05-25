<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;

class DispatchController extends Controller {

    public function GetDrivers(Request $req) {
        $driverRepo = new Repos\DriverRepo();
        $drivers = $driverRepo->ListAllWithEmployeeAndContact();
        return json_encode($drivers);
    }

    public function view() {
        return view ('dispatch.dispatch');
    }

    public function AssignBillToDriver(Request $req) {
        //TODO: not calculating completion percentage
        DB::beginTransaction();
        try {
            $billRepo = new Repos\BillRepo();
            $driverRepo = new Repos\DriverRepo();
            $driver = $driverRepo->GetById($req->driver_id);

            $billRepo->AssignToDriver($req->bill_id, $driver);

            DB::commit();
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function SetBillPickupOrDeliveryTime(Request $req) {
        //TODO: not calculating completion percentage
        DB::beginTransaction();
        try {
            $billRepo = new Repos\BillRepo();

            $billRepo->SetBillPickupOrDeliveryTime($req->bill_id, $req->type, $req->time);

            DB::commit();
            return response()->json([
                'success' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

?>
