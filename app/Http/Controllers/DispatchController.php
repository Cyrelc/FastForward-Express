<?php

namespace App\Http\Controllers;

use App\Events\BillUpdated;
use Illuminate\Http\Request;
use DB;

use App\Http\Repos;

class DispatchController extends Controller {
    public function AssignBillToDriver(Request $req) {
        $billRepo = new Repos\BillRepo();
        $bill = $billRepo->GetById($req->bill_id);
        if($req->user()->cannot('updateDispatch', $bill))
            abort(403);

        DB::beginTransaction();

        if(isset($req->employee_id)) {
            $employeeRepo = new Repos\EmployeeRepo();
            $employee = $employeeRepo->GetById($req->employee_id, null);

            $bill = $billRepo->AssignToDriver($req->bill_id, $employee);
        } else
            $bill = $billRepo->AssignToDriver($req->bill_id, null);

        DB::commit();

        event(new BillUpdated($bill));

        return response()->json([
            'success' => true
        ]);
    }

    public function GetBills(Request $req) {
        if($req->user()->cannot('viewDispatch', Bill::Class))
            abort(403);

        $billRepo = new Repos\BillRepo();
        $bills = $billRepo->GetForDispatch($req);

        return json_encode($bills);
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
