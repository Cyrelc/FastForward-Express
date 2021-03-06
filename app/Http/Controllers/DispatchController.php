<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;

class DispatchController extends Controller {
    public function AssignBillToDriver(Request $req) {
        $billRepo = new Repos\BillRepo();
        $bill = $billRepo->GetById($req->bill_id);
        if($user->cannot('updateDispatch', $bill))
            abort(403);

        DB::beginTransaction();

        $employeeRepo = new Repos\EmployeeRepo();
        $employee = $employeeRepo->GetById($req->employee_id);

        $billRepo->AssignToDriver($req->bill_id, $employee);

        DB::commit();

        return response()->json([
            'success' => true
        ]);
    }

    public function GetDrivers(Request $req) {
        if($req->user()->cannot('viewAll', Employee::class))
            abort(403);

        $employeeRepo = new Repos\EmployeeRepo();
        $employees = $employeeRepo->GetActiveDriversWithContact();

        return json_encode($employees);
    }

    public function SetBillPickupOrDeliveryTime(Request $req) {
        $billRepo = new Repos\BillRepo();
        $bill = $billRepo->GetById($req->bill_id);
        if($req->user()->cannot('updateDispatch', $bill))
            abort(403);

        DB::beginTransaction();

        $billRepo->SetBillPickupOrDeliveryTime($req->bill_id, $req->type, new \DateTime($req->time));

        DB::commit();
        return response()->json([
            'success' => true,
        ]);
    }
}

?>
