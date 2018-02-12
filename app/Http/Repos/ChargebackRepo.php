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
                'count_remaining' => isset($req->continuous) ? 0 : $req->charge_count,
                'start_date' => (new \DateTime($req->input('start_date')))->format('Y-m-d')
            ];

            $new->insert($chargeback);
        }
    }
}
?>
