<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Employee;
use App\Events\BillUpdated;
use Illuminate\Http\Request;
use DB;

use App\Http\Repos;

class DispatchController extends Controller {
    public function assignBillToDriver(Request $req) {
        $billRepo = new Repos\BillRepo();
        $bill = Bill::find($req->bill_id);
        if($req->user()->cannot('updateDispatch', $bill))
            abort(403);

        DB::beginTransaction();

        if(isset($req->employee_id)) {
            $employeeRepo = new Repos\EmployeeRepo();
            $employee = Employee::find($req->employee_id);

            $bill = $billRepo->AssignToDriver($req->bill_id, $employee);
        } else
            $bill = $billRepo->AssignToDriver($req->bill_id, null);

        DB::commit();

        event(new BillUpdated($bill));

        return response()->json([
            'success' => true
        ]);
    }
    public function getBills(Request $req) {
        if($req->user()->cannot('viewDispatch', Bill::Class))
            abort(403);

        $billRepo = new Repos\BillRepo();
        $bills = $billRepo->GetForDispatch($req);

        return json_encode($bills);
    }

    public function getModel(Request $req) {
        if($req->user()->cannot('viewDispatch', Dispatch::class))
            abort(403);

        return response()->json([
            'success' => true,
            'pusher_key' => config('broadcasting.connections.pusher.key'),
            'pusher_cluster' => config('broadcasting.connections.pusher.options.cluster')
        ]);
    }

    public function setBillPickupOrDeliveryTime(Request $req) {
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
