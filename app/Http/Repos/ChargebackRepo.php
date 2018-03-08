<?php
namespace App\Http\Repos;

use DB;
use App\Chargeback;

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

    public function DeactivateById($id) {
        $chargeback = $this->GetById($id);
        $chargeback->count_remaining = 0;
        $chargeback->continuous = 0;

        $chargeback->save();
        return;
    }

    public function GetActiveByEmployeeId($employee_id, $start_date = '9999-01-01') {
        $chargebacks = Chargeback::where('employee_id', $employee_id)->whereDate('start_date', '<=', $start_date)->where('count_remaining', '>', 0)
                        ->orWhere('employee_id', $employee_id)->whereDate('start_date', '<=', $start_date)->where('continuous', true)->get();

        return $chargebacks;
    }

    public function GetById($chargeback_id) {
        $chargeback = Chargeback::where('chargeback_id', $chargeback_id)->first();

        return $chargeback;
    }

    public function GetByManifestId($manifest_id) {
        return Chargeback::where('manifest_id', $manifest_id)
                ->select('name', 'gl_code', 'description', DB::raw('format(amount, 2) as amount'))
                ->get();
    }

    public function GetChargebackTotalByManifestId($manifest_id) {
        $amount = Chargeback::where('manifest_id', $manifest_id)->sum('amount');

        return $amount;
    }

    public function RunChargebacksForManifest($manifest) {
        $driverRepo = new DriverRepo();
        $employee_id = $driverRepo->GetById($manifest->driver_id)->employee_id;
        $chargebacks = $this->GetActiveByEmployeeId($employee_id, $manifest->date_run);
        foreach($chargebacks as $chargeback) {
            if($chargeback->continuous) {
                $new = $chargeback->replicate();
                $new->continuous = false;
                $new->manifest_id = $manifest->manifest_id;
                $new->save();
            } else if ($chargeback->count_remaining == 1) {
                $chargeback->count_remaining = 0;
                $chargeback->manifest_id = $manifest->manifest_id;
                $chargeback->save();
            } else {
                $chargeback->count_remaining--;
                $chargeback->save();
                $new = $chargeback->replicate();
                $new->count_remaining = 0;
                $new->manifest_id = $manifest->manifest_id;
                $new->save();
            }
        }
        return;
    }

    public function Update($id, $req) {
        $fields = array('name', 'start_date', 'amount', 'gl_code', 'count_remaining');
        $chargeback = $this->GetById($id);

        foreach($fields as $field)
            $chargeback->$field = $req->$field;

        $chargeback->continuous = isset($req->continuous);
        if($chargeback->continuous)
            $chargeback->count_remaining = 0;

        $chargeback->save();
    }
}
?>
