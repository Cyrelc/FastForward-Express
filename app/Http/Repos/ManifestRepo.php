<?php
namespace App\Http\Repos;

use DB;
use App\Bill;
use App\Manifest;

class ManifestRepo {
    public function Create($driver_ids, $start_date, $end_date) {
        $manifests = array();
        $chargebackRepo = new ChargebackRepo();

        foreach($driver_ids as $driver_id) {
            $manifest = $this->GenerateManifest($driver_id, $start_date, $end_date);
            $this->ManifestBills($manifest->manifest_id, $driver_id, $start_date, $end_date);
            $chargebackRepo->RunChargebacksForManifest($manifest);
            array_push($manifests, $manifest);
        }

        return $manifests;
    }

    public function GenerateManifest($driver_id, $start_date, $end_date) {
        $manifest = [
            'driver_id' => $driver_id,
            'date_run' => date('Y-m-d'),
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
        $new = new Manifest();
        return $new->create($manifest);
    }

    public function GetById($manifest_id) {
        return Manifest::where('manifest_id', $manifest_id)->first();
    }

    public function ListAll() {
        $manifests = Manifest::leftJoin('drivers', 'drivers.driver_id', '=', 'manifests.driver_id')
                ->leftJoin('employees', 'employees.employee_id', '=', 'drivers.employee_id')
                ->leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
                ->select('manifest_id',
                    'drivers.driver_id',
                    'employees.employee_id',
                    DB::raw('concat(first_name, " ", last_name) as employee_name'),
                    DB::raw('(select count(*) from bills where pickup_manifest_id = manifests.manifest_id or delivery_manifest_id = manifests.manifest_id) as bill_count'),
                    'date_run',
                    'manifests.start_date',
                    'end_date')
                ->get();
        return $manifests;
    }

    public function ManifestBills($manifest_id, $driver_id, $start_date, $end_date) {
        $pickup_bills = Bill::where(function ($query) use ($driver_id, $start_date, $end_date) {
            $query->whereDate('pickup_date_scheduled', '>=', $start_date)
            ->whereDate('pickup_date_scheduled', '<=', $end_date)
            ->where('pickup_driver_id', $driver_id)
            ->where('pickup_manifest_id', null)
            ->where('percentage_complete', 1);
        })->get();

        $delivery_bills = Bill::where(function ($query) use ($driver_id, $start_date, $end_date){
            $query->whereDate('pickup_date_scheduled', '>=', $start_date)
            ->whereDate('pickup_date_scheduled', '<=', $end_date)
            ->where('delivery_driver_id', $driver_id)
            ->where('delivery_manifest_id', null)
            ->where('percentage_complete', 1);
        })->get();

        foreach($pickup_bills as $bill) {
            $bill->pickup_manifest_id = $manifest_id;
            $bill->save();
        }

        foreach($delivery_bills as $bill) {
            $bill->delivery_manifest_id = $manifest_id;
            $bill->save();
        }
    }
}
