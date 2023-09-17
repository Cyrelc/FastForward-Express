<?php
namespace App\Http\Repos;

use DB;
use App\Bill;
use App\Chargeback;
use App\Employee;
use App\LineItem;
use App\Manifest;
use App\DriverChargeback;
use App\Http\Filters\DateBetween;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class ManifestRepo {
    public function Create($employeeIds, $startDate, $endDate) {
        $manifests = array();
        $chargebackRepo = new ChargebackRepo();

        foreach($employeeIds as $employeeId) {
            $manifest = $this->GenerateManifest($employeeId, $startDate, $endDate);
            $this->ManifestLineItems($manifest->manifest_id, $employeeId, $startDate, $endDate);
            $chargebackRepo->RunChargebacksForManifest($manifest);
            $manifests[] = $manifest;
        }

        return $manifests;
    }

    public function Delete($manifestId) {
        $pickupLineItems = LineItem::where('pickup_manifest_id', $manifestId)->get();
        $deliveryLineItems = LineItem::where('delivery_manifest_id', $manifestId)->get();
        $chargebacks = Chargeback::where('manifest_id', $manifestId)->get();
        $manifest = Manifest::where('manifest_id', $manifestId);

        foreach($pickupLineItems as $lineItem) {
            $lineItem->pickup_manifest_id = null;

            $lineItem->save();
        }
        foreach($deliveryLineItems as $lineItem) {
            $lineItem->delivery_manifest_id = null;

            $lineItem->save();
        }
        foreach($chargebacks as $chargeback) {
            if($chargeback->continuous == 0) {
                $temp = Chargeback::where('chargeback_id', $chargeback->chargeback_id)->first();
                $temp->count_remaining++;
                $temp->save();
            }
            $chargebackLineItems = LineItem::where('chargeback_id', $chargeback->chargeback_id)->update(['chargeback_id' => null]);
            $chargeback->delete();
        }
        $manifest->delete();
        return;
    }

    public function GenerateManifest($employeeId, $startDate, $endDate) {
        $manifest = [
            'employee_id' => $employeeId,
            'date_run' => date('Y-m-d'),
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $new = new Manifest();
        return $new->create($manifest);
    }

    public function GetMonthlyEmployeePay($date) {
        $income = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereNotNull('delivery_manifest_id')
            ->whereNotNull('pickup_manifest_id')
            ->leftjoin('manifests as pickup_manifest', 'pickup_manifest.manifest_id', '=', 'line_items.pickup_manifest_id')
            ->leftjoin('manifests as delivery_manifest', 'delivery_manifest.manifest_id', '=', 'line_items.delivery_manifest_id')
            ->whereDate('pickup_manifest.end_date', '>=', $date)
            ->whereDate('delivery_manifest.end_date', '>=', $date)
            ->select(
                DB::raw('sum(round(driver_amount * pickup_driver_commission, 2) + round(driver_amount * delivery_driver_commission, 2)) as employee_income'),
                DB::raw('date_format(pickup_manifest.end_date, "%Y-%m") as month')
            )->groupBy('month')
            ->orderBy('month');

        return $income->get();
    }

    public function GetById($manifest_id) {
        return Manifest::where('manifest_id', $manifest_id)->first();
    }

    public function ListAll($req, $employeeId = null) {
        $manifests = Manifest::leftJoin('employees', 'employees.employee_id', '=', 'manifests.employee_id')
            ->leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->select(
                'manifest_id',
                'employees.employee_id',
                DB::raw('concat(first_name, " ", last_name) as employee_name'),
                DB::raw('(select count(distinct bill_id) from line_items
                    left join charges on charges.charge_id = line_items.charge_id
                    where pickup_manifest_id = manifests.manifest_id or
                    delivery_manifest_id = manifests.manifest_id)
                as bill_count'),
                'date_run',
                'manifests.start_date',
                'end_date'
            );

        if($employeeId)
            $manifests->where('employees.employee_id', $employeeId);

        $filteredManifests = QueryBuilder::for($manifests)
            ->allowedFilters([
                AllowedFilter::exact('driver_id', 'manifests.employee_id'),
                AllowedFilter::custom('start_date', new DateBetween, 'manifests.start_date'),
                AllowedFilter::custom('end_date', new DateBetween)
            ]);

        return $filteredManifests->get();
    }

    public function Regather($manifestId) {
        $manifest = $this->GetById($manifestId);
        $this->ManifestLineItems(
            $manifest->manifest_id,
            $manifest->employee_id,
            $manifest->start_date,
            $manifest->end_date
        );
    }

    /**
     * Private functions
     */

    private function ManifestLineItems($manifestId, $driverId, $startDate, $endDate) {
        $chargebackRepo = new ChargebackRepo();

        $pickupLineItems = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereBetween(DB::raw('date(time_pickup_scheduled)'), [$startDate, $endDate])
            ->where('driver_amount', '!=', 0)
            ->where(DB::raw('coalesce(line_items.pickup_driver_id, bills.pickup_driver_id)'), $driverId)
            ->where('pickup_manifest_id', null)
            ->where('percentage_complete', 100)
            ->update(['pickup_manifest_id' => $manifestId, 'line_items.pickup_driver_id' => $driverId]);

        $deliveryLineItems = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereBetween(DB::raw('date(time_pickup_scheduled)'), [$startDate, $endDate])
            ->where('driver_amount', '!=', 0)
            ->where(DB::raw('coalesce(line_items.delivery_driver_id, bills.delivery_driver_id)'), $driverId)
            ->where('delivery_manifest_id', null)
            ->where('percentage_complete', 100)
            ->update(['delivery_manifest_id' => $manifestId, 'line_items.delivery_driver_id' => $driverId]);

        $chargebackBills = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereBetween(DB::raw('date(time_pickup_scheduled)'), [$startDate, $endDate])
            ->where('charge_employee_id', $driverId)
            ->whereRaw('charge_type_id = (select payment_type_id from payment_types where name = "Employee")')
            ->where('price', '!=', 0)
            ->where('percentage_complete', 100)
            ->where('line_items.chargeback_id', null)
            ->select(
                DB::raw('sum(price) as price'),
                'charges.bill_id as bill_id',
                'charges.charge_id as charge_id',
                DB::raw('date(time_pickup_scheduled) as date_pickup_scheduled')
            )->groupBy('charges.bill_id')
            ->get();

        foreach($chargebackBills as $chargeback) {
            $billChargeback = [
                'bill_id' => $chargeback->bill_id,
                'employee_id' => $driverId,
                'manifest_id' => null,
                'amount' => $chargeback->price,
                'gl_code' => null,
                'name' => 'Chargeback for bill ' . $chargeback->bill_id,
                'description' => '',
                'continuous' => false,
                'count_remaining' => 1,
                'start_date' => $chargeback->date_pickup_scheduled
            ];

            $newChargeback = $chargebackRepo->CreateBillChargeback($billChargeback);

            $chargebackLineItems = LineItem::where('charge_id', $chargeback->charge_id)
                ->update(['chargeback_id' => $newChargeback->chargeback_id]);
        }
    }
}
