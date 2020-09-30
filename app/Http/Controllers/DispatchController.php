<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;

class DispatchController extends Controller {

    public function GetDrivers(Request $req) {
        $employeeRepo = new Repos\EmployeeRepo();
        $employees = $employeeRepo->GetActiveDriversWithContact();
        return json_encode($employees);
    }

    public function view() {
        return view ('dispatch.dispatch');
    }

    public function AssignBillToDriver(Request $req) {
        //TODO: not calculating completion percentage
        DB::beginTransaction();
        try {
            $billRepo = new Repos\BillRepo();
            $employeeRepo = new Repos\EmployeeRepo();
            $employee = $employeeRepo->GetById($req->employee_id);

            $billRepo->AssignToDriver($req->bill_id, $employee);

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

            $billRepo->SetBillPickupOrDeliveryTime($req->bill_id, $req->type, new \DateTime($req->time));

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
