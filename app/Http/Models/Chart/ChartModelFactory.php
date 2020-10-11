<?php

namespace App\Http\Models\Chart;

use App\Http\Repos;
use App\Http\Models\Chart;

class ChartModelFactory {
    public function GetAdminDashboardChart() {
        $billRepo = new Repos\BillRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $manifestRepo = new Repos\ManifestRepo();

        $comparisonDate = date('Y-m-01');
        $comparisonDate = date('Y-m-01', strtotime($comparisonDate . ' - 1 year'));

        $accountsPayable = $manifestRepo->GetMonthlyEmployeePay($comparisonDate);
        $prepaidBillTotals = $billRepo->GetPrepaidMonthlyTotals($comparisonDate);
        $allBillTotals = $billRepo->GetMonthlyTotals($comparisonDate);

        $model = [];
        if(sizeof($accountsPayable) > 0) {
            $employeePay = [];
            foreach($accountsPayable as $payable)
                array_push($employeePay, ['x' => $payable->month, 'y' => $payable->employee_income]);
            array_push($model, ['id' => 'Employee Pay', 'data' => $employeePay]);
        }
        if(sizeof($prepaidBillTotals) > 0) {
            $prepaidData = [];
            foreach($prepaidBillTotals as $total)
                array_push($prepaidData, ['x' => $total->month, 'y' => $total->prepaid_income]);
            array_push($model, ['id' => 'Prepaid Income', 'data' => $prepaidData]);
        }
        $billTotals = [];
        $interlinerCost = [];
        $interlinerCostToCustomer = [];
        foreach($allBillTotals as $total) {
            array_push($billTotals, ['x' => $total->month, 'y' => $total->income]);
            array_push($interlinerCost, ['x' => $total->month, 'y' => $total->interliner_cost]);
            array_push($interlinerCostToCustomer, ['x' => $total->month, 'y' => $total->interliner_cost_to_customer]);
        }
        array_push($model, ['id' => 'Total Income', 'data' => $billTotals]);
        array_push($model, ['id' => 'Interliner Cost', 'data' => $interlinerCost]);
        array_push($model, ['id' => 'Interliner Cost to Customer', 'data' => $interlinerCostToCustomer]);

        return $model;
    }

    public function GetCalendarHeatChart($accountId = null) {
        $billRepo = new Repos\BillRepo();
        $bills = $billRepo->GetCalendarHeatChart($accountId);

        return $bills;
    }

    public function GetMonthlyBills($dateGroupBy, $startDate, $endDate, $groupBy, $summationType) {
        if($dateGroupBy === 'day') {
            $startDate = date("Y-m-01", strtotime($endDate));
            $endDate = date("Y-m-t", strtotime($endDate));
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

