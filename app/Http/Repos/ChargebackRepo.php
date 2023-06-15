<?php
namespace App\Http\Repos;

use DB;
use App\Chargeback;
use App\DriverChargeback;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

use App\Http\Filters\ChargebackFilters\Active;

class ChargebackRepo {
    public function ListAll($req) {
        $chargebacks = Chargeback::leftjoin('employees', 'employees.employee_id', '=', 'chargebacks.employee_id')
            ->leftjoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
            ->select(
                'chargeback_id',
                DB::raw('concat(first_name, " ", last_name) as employee_name'),
                'employees.employee_id',
                'employee_number',
                'chargebacks.start_date as chargeback_start_date',
                'chargebacks.name as chargeback_name',
                'continuous',
                'count_remaining',
                'gl_code',
                'amount'
            );

        $filteredChargebacks = QueryBuilder::for($chargebacks)
            ->allowedFilters([
                AllowedFilter::custom('active', new Active),
                AllowedFilter::exact('employee_id', 'chargebacks.employee_id'),
                // AllowedFilter::custom('manifested', new Manifested)
            ]);

        return $chargebacks->get();
    }

    public function CreateChargebacks($req) {
        foreach($req->employee_ids as $employee) {
            $new = new Chargeback;
            $chargeback = [
                'employee_id' => $employee,
                'amount' => $req->amount,
                'gl_code' => $req->gl_code == '' ? null : $req->gl_code,
                'name' => $req->name,
                'description' => $req->description == '' ? null : $req->description,
                'continuous' => filter_var($req->continuous, FILTER_VALIDATE_BOOLEAN),
                'count_remaining' => filter_var($req->continuous, FILTER_VALIDATE_BOOLEAN) ? 0 : $req->count_remaining,
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

    public function Delete($chargebackId) {
        $old = Chargeback::where('chargeback_id', $chargebackId)->first();

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

    public function GetById($chargebackId) {
        $chargeback = Chargeback::where('chargeback_id', $chargebackId);

        return $chargeback->first();
    }

    public function GetByManifestId($manifest_id) {
        $chargebacks = Chargeback::where('chargebacks.manifest_id', $manifest_id)
            ->select(
                'amount',
                'name',
                'gl_code',
                'description'
            );

        return $chargebacks->get();
    }

    public function GetChargebackTotalByManifestId($manifestId) {
        $amount = Chargeback::where('chargebacks.manifest_id', $manifestId)
            ->sum('amount');

        return $amount;
    }

    public function RunChargebacksForManifest($manifest) {
        $employeeRepo = new EmployeeRepo();

        $chargebacks = $this->GetActiveByEmployeeId($manifest->employee_id, $manifest->date_run);

        foreach($chargebacks as $chargeback) {
            $new = new Chargeback;
            $new->manifest_id = $manifest->manifest_id;
            $new->amount = $chargeback->amount;
            $new->name = $chargeback->name;
            $new->description = $chargeback->description;
            $new->employee_id = $chargeback->employee_id;
            $new->save();
            if($chargeback->continuous == false) {
                $chargeback->count_remaining--;
                $chargeback->save();
            }
        }
        return;
    }

    public function Update($req) {
        $fields = array('name', 'amount', 'count_remaining', 'description');
        $chargeback = $this->GetById($req->chargeback_id);

        foreach($fields as $field)
            if(isset($req->$field))
                $chargeback->$field = $req->$field;

        $chargeback->description = $req->description ?? null;
        $chargeback->gl_code = $req->gl_code === '' ? null : $req->gl_code;
        $chargeback->start_date = new \DateTime($req->start_date);
        $chargeback->continuous = filter_var($req->continuous, FILTER_VALIDATE_BOOLEAN);
        if($chargeback->continuous)
            $chargeback->count_remaining = 0;

        $chargeback->save();
    }
}
?>
