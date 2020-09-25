<?php

namespace App\Http\Models\Chart;

use App\Http\Repos;
use App\Http\Models\Chart;

class ChartModelFactory {
    public function GetMonthlyBills($dateGroupBy, $startDate, $endDate, $groupBy, $summationType) {
        if($dateGroupBy === 'day') {
            $startDate = date("Y-m-01", strtotime($startDate));
            $endDate = date("Y-m-t", strtotime($startDate));
        } else if ($dateGroupBy === 'month') {
            $startDate = date("Y-m-01", strtotime($startDate));
            $endDate = date("Y-m-t", strtotime($endDate));
        } else {
            // else assumes "year"
            $startDate = date("Y-01-01", strtotime($startDate));
            $endDate = date("Y-12-31", strtotime($endDate));
        }
        $billRepo = new Repos\BillRepo();

        $model = new \stdClass();
        $model->keys = [];

        $bills = $billRepo->GetChartMonthly($dateGroupBy, $startDate, $endDate, $groupBy);

        if(sizeof($bills) != 0) {
            foreach($bills as $billResult) {
                $groupByValue = $groupBy === 'none' ? $billResult->$dateGroupBy : $billResult->$groupBy;
                $model->bills[$billResult->$dateGroupBy][strval($groupByValue)] = $billResult->$summationType;
                if(!in_array($groupByValue, $model->keys))
                    $model->keys = array_merge($model->keys, [$groupByValue]);
            }
            foreach($model->bills as $key => $value)
                $model->bills[$key]['indexKey'] = $key;
        }

        return $model;
    }
}

