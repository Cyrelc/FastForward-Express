<?php
/**
 * Created by PhpStorm.
 * User: jacks
 * Date: 6/22/2017
 * Time: 7:39 PM
 */

namespace App\Http\Collectors;


class CommissionCollector {
    public function Collect($req, $prefix, $accountId) {
        return [
            'commission_id' => $req->input($prefix . '-id'),
            'account_id' => $accountId,
            'employee_id' => $req->input($prefix . '-employee-id'),
            'commission' => $req->input($prefix . '-percent'),
            'depreciation_amount' => $req->input($prefix . '-depreciation-percent'),
            'years' => $req->input($prefix . '-depreciation-duration'),
            'start_date' => $req->input($prefix . '-depreciation-start-date')
        ];
    }

    public function Remerge($req, $model) {
        if ($req->old('should-give-commission-1') !== null)
            $model->give_commission_1 = $req->old('should-give-commission-1') === "true";
        if ($req->old('should-give-commission-2') !== null)
            $model->give_commission_2 = $req->old('should-give-commission-1') === "true";

        if ($req->old('account-id') === null) {
            if ($req->old('should-give-commission-1') === "true") {
                $com = new \App\DriverCommission();

                if ($req->old('commission-1-employee-id') !== null)
                    $com["employee_id"] = $req->old('commission-1-employee-id');
                if ($req->old('commission-1-percent') !== null)
                    $com["commission"] = $req->old('commission-1-percent') / 100;
                if ($req->old('commission-1-depreciate-percentage') !== null)
                    $com["depreciation_amount"] = $req->old('commission-1-depreciate-percentage') / 100;
                if ($req->old('commission-1-depreciate-duration') !== null)
                    $com["years"] = $req->old('commission-1-depreciate-duration');
                if ($req->old('commission-1-depreciate-start-date') !== null)
                    $com["start_date"] = strtotime($req->old('commission-1-depreciate-start-date'));

                $model->commissions[0] = $com;
            }

            if ($req->old('should-give-commission-2') === "true") {
                $com = new \App\DriverCommission();

                if ($req->old('commission-2-employee-id') !== null)
                    $com["employee_id"] = $req->old('commission-2-employee-id');
                if ($req->old('commission-2-percent') !== null)
                    $com["commission"] = $req->old('commission-2-percent') / 100;
                if ($req->old('commission-2-depreciate-percentage') !== null)
                    $com["depreciation_amount"] = $req->old('commission-2-depreciate-percentage') / 100;
                if ($req->old('commission-2-depreciate-duration') !== null)
                    $com["years"] = $req->old('commission-2-depreciate-duration');
                if ($req->old('commission-2-depreciate-start-date') !== null)
                    $com["start_date"] = strtotime($req->old('commission-2-depreciate-start-date'));


                $model->commissions[1] = $com;
            }
        } else {

            if ($model->give_commission_1) {
                $com = $model->commissions[0];

                if ($req->old('commission-1-employee-id') !== null)
                    $com["employee_id"] = $req->old('commission-1-employee-id');
                if ($req->old('commission-1-percent') !== null)
                    $com["commission"] = $req->old('commission-1-percent') / 100;
                if ($req->old('commission-1-depreciate-percentage') !== null)
                    $com["depreciation_amount"] = $req->old('commission-1-depreciate-percentage') / 100;
                if ($req->old('commission-1-depreciate-duration') !== null)
                    $com["years"] = $req->old('commission-1-depreciate-duration');
                if ($req->old('commission-1-depreciate-start-date') !== null)
                    $com["start_date"] = strtotime($req->old('commission-1-depreciate-start-date'));

                $model->commissions[0] = $com;
            }

            if ($model->give_commission_2) {
                $com = $model->commissions[1];

                if ($req->old('commission-2-employee-id') !== null)
                    $com["employee_id"] = $req->old('commission-2-employee-id');
                if ($req->old('commission-2-percent') !== null)
                    $com["commission"] = $req->old('commission-2-percent') / 100;
                if ($req->old('commission-2-depreciate-percentage') !== null)
                    $com["depreciation_amount"] = $req->old('commission-2-depreciate-percentage') / 100;
                if ($req->old('commission-2-depreciate-duration') !== null)
                    $com["years"] = $req->old('commission-2-depreciate-duration');
                if ($req->old('commission-2-depreciate-start-date') !== null)
                    $com["start_date"] = strtotime($req->old('commission-2-depreciate-start-date'));

                $model->commissions[1] = $com;
            }
        }

        return $model;
    }
}