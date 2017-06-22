<?php
namespace App\Http\Repos;

use App\Bill;

class BillRepo {
    public function CountByDriver($driverId, $date) {
        $val = \DB::table('bills')
            ->select(\DB::raw('count(bill_id) as bill_count'))
            ->where('driver_id', '=', $driverId)
            ->where('date', '>', $date)
            ->get();

        return $val[0]->bill_count;
    }
}