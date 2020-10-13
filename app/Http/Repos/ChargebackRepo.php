<?php
namespace App\Http\Repos;

use DB;
use App\Chargeback;
use App\DriverChargeback;

class ChargebackRepo {

    public function CreateChargebacks($req) {
        foreach($req->employees as $employee) {
            $new = new Chargeback;
            $chargeback = [
                'employee_id' => $employee,
                'amount' => $req->amount,
                'gl_code' => $req->gl_code == '' ? null : $req->gl_code,
                'name' => $req->name,
                'description' => $req->description == '' ? null : $req->description,
                'continuous' => isset($req->continuous),
                'count_remaining' => $req->continuous == true ? 0 : $req->charge_count,
                'start_date' => (new \DateTime($req->input('start_date')))->format('Y-m-d')
            ];

            $new->insert($chargeback);
        }
    }

    public function CreateBillChargeback($chargeback) {
        $new = new Chargeback;

        $new = $new->create($chargeback);

        return $new;
    }

    public function DeactivateById($id) {
        $chargeback = $this->GetById($id);
        $chargeback->count_remaining = 0;
        $chargeback->continuous = 0;

        $chargeback->save();
        return;
    }

    public function Delete($id) {
        $old =  $this->GetById($id);

        $old->delete();
        return;
    }

    public function GetActiveByEmployeeId($employeeId, $startDate = '9999-12-31') {
        $chargebacks = Chargeback::where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $startDate)
            ->where(function($query) {
                $query->where('count_remaining', '>', 0)
                ->orWhere('continuous', 1);
            });

        return $chargebacks->get();
    }

    public function GetById($chargeback_id) {
        $chargeback = Chargeback::where('chargeback_id', $chargeback_id)->first();

        return $chargeback;
    }

    public function GetByManifestId($manifest_id) {
        return DriverChargeback::where('driver_chargebacks.manifest_id', $manifest_id)
                ->join('chargebacks', 'chargebacks.chargeback_id', '=', 'driver_chargebacks.chargeback_id')
                ->select('name', 'gl_code', 'description', DB::raw('format(amount, 2) as amount'))
                ->get();
    }

    public function GetChargebackTotalByManifestId($manifest_id) {
        $amount = DriverChargeback::where('driver_chargebacks.manifest_id', $manifest_id)
                ->join('chargebacks', 'chargebacks.chargeback_id', '=', 'driver_chargebacks.chargeback_id')
                ->sum('amount');

        return $amount;
    }

    public function RunChargebacksForManifest($manifest) {
        $employeeRepo = new EmployeeRepo();
        $chargebacks = $this->GetActiveByEmployeeId($manifest->employee_id, $manifest->date_run);
        foreach($chargebacks as $chargeback) {
            $new = new DriverChargeback;
            $new->chargeback_id = $chargeback->chargeback_id;
            $new->manifest_id = $manifest->manifest_id;
            $new->save();
            if($chargeback->continuous == false) {
                $chargeback->count_remaining--;
                $chargeback->save();
            }
        }
        return;
    }

    public function Update($id, $req) {
        $fields = array('name', 'start_date', 'amount', 'gl_code', 'count_remaining');
        $chargeback = $this->GetById($id);

        foreach($fields as $field)
            if(isset($req->$field))
                $chargeback->$field = $req->$field;

        $chargeback->continuous = isset($req->continuous);
        if($chargeback->continuous)
            $chargeback->count_remaining = 0;

        $chargeback->save();
    }
}
?>
