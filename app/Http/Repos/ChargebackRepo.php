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

    public function GetActiveByEmployeeId($employee_id) {
        $chargebacks = Chargeback::where('employee_id', $employee_id)->where('count_remaining', '>', 0)
                        ->orWhere('employee_id', $employee_id)->where('continuous', true)->get();

        return $chargebacks;
    }

    public function GetById($chargeback_id) {
        $chargeback = Chargeback::where('chargeback_id', $chargeback_id)->first();

        return $chargeback;
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
