<?php
namespace App\Http\Repos;

use App\Bill;

class BillRepo {

	public function ListAll() {
		$bills = Bill::All();

		return $bills;
	}

    public function GetById($id) {
	    $bill = Bill::where('bill_id', '=', $id)->first();

	    return $bill;
    }

    public function CountByDriver($driverId) {
	    $bills = \DB::table("bills")->select(\DB::raw('count(bill_id) as bill_count'))
            ->where('pickup_driver_id', '=', $driverId)
            ->orWhere('delivery_driver_id', '=', $driverId)
            ->get();

	    return $bills[0]->bill_count;
    }
}
