<?php
namespace App\Http\Models\Manifest;

use App\Http\Models;
use App\Http\Repos;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;

class ManifestModelFactory{
    public function ListAll($req, $employeeId = null) {
        $chargebackRepo = new Repos\ChargebackRepo;
        $manifestRepo = new Repos\ManifestRepo();

        $manifests = $manifestRepo->ListAll($req, $employeeId);

        // Totals for every listed manifest at once, rather than two queries per manifest
        $manifestIds = $manifests->pluck('manifest_id')->all();
        $totals = $manifestRepo->GetListTotals($manifestIds);
        $chargebackTotals = $chargebackRepo->GetChargebackTotalsByManifestIds($manifestIds);

        foreach($manifests as $manifest) {
            $total = $totals->get($manifest->manifest_id);
            $manifest->bill_count = $total ? (int)$total->bill_count : 0;
            $manifest->driver_gross = $total?->driver_gross;
            $manifest->driver_chargeback_amount = $chargebackTotals->get($manifest->manifest_id, 0);
            $manifest->driver_income = $manifest->driver_gross - $manifest->driver_chargeback_amount;
        }

        return $manifests;
    }

    public function GetById($user, $manifestId) {
        $addressRepo = new Repos\AddressRepo();
        $billRepo = new Repos\BillRepo();
        $chargebackRepo = new Repos\ChargebackRepo();
        $lineItemRepo = new Repos\LineItemRepo();
        $manifestRepo = new Repos\ManifestRepo();

        $permissionModelFactory = new Models\Permission\PermissionModelFactory();

        $model = new ManifestViewModel();

        $model->manifest = $manifestRepo->GetById($manifestId);
        $model->bill_count = $billRepo->CountByManifestId($manifestId);

        //Handle Employee Information
        $model->employee = new EmployeeResource(Employee::findOrFail($model->manifest->employee_id));

        $model->bills = $billRepo->GetByManifestId($manifestId);
        $model->overview = $billRepo->GetManifestOverviewById($manifestId);

        $driverTotal = $lineItemRepo->GetDriverTotalByManifestId($manifestId);
        $model->driver_total = number_format($driverTotal, 2);

        $model->chargebacks = $chargebackRepo->GetByManifestId($manifestId);

        $chargebackTotal = $chargebackRepo->GetChargebackTotalByManifestId($manifestId);
        $model->chargeback_total = number_format($chargebackTotal, 2);

        $model->driver_income = number_format($driverTotal - $chargebackTotal, 2);

        return $model;
    }

    public function GetDriverListModel($req) {
        $employeeRepo = new Repos\EmployeeRepo();

        $startDate = (new \DateTime($req->start_date))->format('Y-m-d');
        $endDate = (new \DateTime($req->end_date))->format('Y-m-d');

        $driversWithBills = $employeeRepo->getEmployeesWithUnmanifestedBillsBetweenDates($startDate, $endDate);

        return $driversWithBills;
    }

    public function GetGenerateModel($start_date = null, $end_date = null) {
        if(isset($start_date) && isset($end_date)) {
            $start_date = (new \DateTime($start_date))->format('Y-m-d');
            $end_date = (new \DateTime($end_date))->format('Y-m-d');
        } else {
            $start_date = date('U', strtotime('first day of previous month'));
            $end_date = date('U', strtotime('last day of previous month'));
        }

        $model = new GenerateManifestViewModel();

        $model->start_date = $start_date;
        $model->end_date = $end_date;

        return $model;
    }
}
