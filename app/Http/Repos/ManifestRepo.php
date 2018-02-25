<?php
namespace App\Http\Repos;

use DB;
use App\Bill;
use App\Manifest;

class ManifestRepo {
    public function Create($driver_ids, $start_date, $end_date) {
        $manifests = array();

        foreach($driver_ids as $driver_id) {
            $manifest = $this->GenerateManifest($driver_id, $start_date, $end_date);
            $this->ManifestBills($manifest->manifest_id, $driver_id, $start_date, $end_date);
            array_push($manifests, $manifest);
        }

        return $manifests;
    }

    public function GetManifestAmountById($manifest_id) {
        $driverRepo = new Repos\DriverRepo();
        $manifest = $this->GetById($manifest_id);

        $driver = $driverRepo->GetById($manifest->driver_id);

        $pickup_subtotal = 0.5 * Bill::where('pickup_manifest_id', $manifest_id)
                ->sum('amount');
        $delivery_subtotal = 0.5 * Bill::where('delivery_manifest_id', $manifest_id)
                ->sum('amount');

        $total = $pickup_subtotal * $driver->pickup_commission;
        $total += $delivery_subtotal * $driver->delivery_commission;

        return $total;
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
        return Manifest::All();
    }

    public function ManifestBills($manifest_id, $driver_id, $start_date, $end_date) {
        $pickup_bills = Bill::where(function ($query) use ($driver_id, $start_date, $end_date) {
            $query->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->where('pickup_driver_id', $driver_id)
            ->where('is_pickup_manifested', false);
        })->get();

        $delivery_bills = Bill::where(function ($query) use ($driver_id, $start_date, $end_date){
            $query->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->where('delivery_driver_id', $driver_id)
            ->where('is_delivery_manifested', false);
        })->get();

        foreach($pickup_bills as $bill) {
            $bill->pickup_manifest_id = $manifest_id;
            $bill->is_pickup_manifested = true;
            $bill->save();
        }

        foreach($delivery_bills as $bill) {
            $bill->delivery_manifest_id = $manifest_id;
            $bill->is_delivery_manifested = true;
            $bill->save();
        }
    }
}
