<?php
namespace App\Http\Repos;

use DB;
use App\Bill;
use App\Chargeback;
use App\Employee;
use App\Manifest;
use App\DriverChargeback;
use App\Http\Filters\DateBetween;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class ManifestRepo {
    public function Create($employee_ids, $start_date, $end_date) {
        $manifests = array();
        $chargebackRepo = new ChargebackRepo();

        foreach($employee_ids as $employee_id) {
            $manifest = $this->GenerateManifest($employee_id, $start_date, $end_date);
            $this->ManifestBills($manifest->manifest_id, $employee_id, $start_date, $end_date);
            $chargebackRepo->RunChargebacksForManifest($manifest);
            array_push($manifests, $manifest);
        }

        return $manifests;
    }

    public function Delete($manifest_id) {
        $pickupBills = Bill::where('pickup_manifest_id', $manifest_id)->get();
        $deliveryBills = Bill::where('delivery_manifest_id', $manifest_id)->get();
        $chargebacks = DriverChargeback::where('manifest_id', $manifest_id)->get();
        $manifest = Manifest::where('manifest_id', $manifest_id);

        foreach($pickupBills as $bill) {
            $bill->pickup_manifest_id = null;

            $bill->save();
        }
        foreach($deliveryBills as $bill) {
            $bill->delivery_manifest_id = null;

            $bill->save();
        }
        foreach($chargebacks as $chargeback) {
            if($chargeback->continuous == 0) {
                $temp = Chargeback::where('chargeback_id', $chargeback->chargeback_id)->first();
                $temp->count_remaining++;
                $temp->save();
            }
            $chargeback->delete();
        }
        $manifest->delete();
        return;
    }

    public function GenerateManifest($employee_id, $start_date, $end_date) {
        $manifest = [
            'employee_id' => $employee_id,
            'date_run' => date('Y-m-d'),
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
        $new = new Manifest();
        return $new->create($manifest);
    }

    public function GetMonthlyEmployeePay($date) {
        $income = Bill::whereNotNull('delivery_manifest_id')
        ->whereNotNull('pickup_manifest_id')
        ->leftjoin('manifests as pickup_manifest', 'pickup_manifest.manifest_id', '=', 'bills.pickup_manifest_id')
        ->leftjoin('manifests as delivery_manifest', 'delivery_manifest.manifest_id', '=', 'bills.delivery_manifest_id')
        ->whereDate('pickup_manifest.end_date', '>=', $date)
        ->whereDate('delivery_manifest.end_date', '>=', $date)
        ->select(
            DB::raw('sum(round(amount * pickup_driver_commission, 2) + round(amount * delivery_driver_commission, 2)) as employee_income'),
            DB::raw('date_format(pickup_manifest.end_date, "%Y-%m") as month')
        )
        ->groupBy('month');

        return $income->get();
    }

    public function GetById($manifest_id) {
        return Manifest::where('manifest_id', $manifest_id)->first();
    }

    public function ListAll() {
        $manifests = Manifest::leftJoin('employees', 'employees.employee_id', '=', 'manifests.employee_id')
                ->leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
                ->select(
                    'manifest_id',
                    'employees.employee_id',
                    DB::raw('concat(first_name, " ", last_name) as employee_name'),
                    DB::raw('(select count(*) from bills where pickup_manifest_id = manifests.manifest_id or delivery_manifest_id = manifests.manifest_id) as bill_count'),
                    'date_run',
                    'manifests.start_date',
                    'end_date',
                    DB::raw('(select sum(case when pickup_manifest_id = manifest_id and delivery_manifest_id = manifest_id then round(amount * pickup_driver_commission, 2) + round(amount * delivery_driver_commission, 2) when pickup_manifest_id = manifest_id then round(amount * pickup_driver_commission, 2) when delivery_manifest_id = manifest_id then round(amount * delivery_driver_commission, 2) end) from bills) as driver_income'),
                    DB::raw('(select sum(amount) from chargebacks left join driver_chargebacks on driver_chargebacks.chargeback_id = chargebacks.chargeback_id where driver_chargebacks.manifest_id = manifests.manifest_id) as driver_chargeback_amount')
                );

        $filteredManifests = QueryBuilder::for($manifests)
            ->allowedFilters([
                AllowedFilter::exact('driver_id', 'manifests.employee_id'),
                AllowedFilter::custom('start_date', new DateBetween),
                AllowedFilter::custom('end_date', new DateBetween)
            ]);

        return $filteredManifests->get();
    }

    public function ManifestBills($manifest_id, $driver_id, $start_date, $end_date) {
        $pickup_bills = Bill::where(function ($query) use ($driver_id, $start_date, $end_date) {
            $query->whereDate('time_pickup_scheduled', '>=', $start_date)
            ->whereDate('time_pickup_scheduled', '<=', $end_date)
            ->where('pickup_driver_id', $driver_id)
            ->where('pickup_manifest_id', null)
            ->where('percentage_complete', 100);
        })->get();

        $delivery_bills = Bill::where(function ($query) use ($driver_id, $start_date, $end_date){
            $query->whereDate('time_pickup_scheduled', '>=', $start_date)
            ->whereDate('time_pickup_scheduled', '<=', $end_date)
            ->where('delivery_driver_id', $driver_id)
            ->where('delivery_manifest_id', null)
            ->where('percentage_complete', 100);
        })->get();

        foreach($pickup_bills as $bill) {
            if($bill->chargeback_id != null && $bill->invoice_id == null && $bill->pickup_manifest_id == null && $bill->delivery_manifest_id == null) {
                $chargeback = Chargeback::where('chargeback_id', $bill->chargeback_id)->first();
                $chargeback->count_remaining = 1;
                $chargeback->save();
            }
            $bill->pickup_manifest_id = $manifest_id;
            $bill->save();
        }

        foreach($delivery_bills as $bill) {
            if($bill->chargeback_id != null && $bill->invoice_id == null && $bill->pickup_manifest_id == null && $bill->delivery_manifest_id == null) {
                $chargeback = Chargeback::where('chargeback_id', $bill->chargeback_id)->first();
                $chargeback->count_remaining = 1;
                $chargeback->save();
            }
            $bill->delivery_manifest_id = $manifest_id;
            $bill->save();
        }
    }
}
