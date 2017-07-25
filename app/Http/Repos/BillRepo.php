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

    public function Insert($bill) {
    	$new = new Bill;

    	$new->charge_account_id = $bill['charge_account_id'];
    	$new->pickup_account_id = $bill['pickup_account_id'];
    	$new->delivery_account_id = $bill['delivery_account_id'];
    	$new->from_address_id = $bill['pickup_address_id'];
    	$new->to_address_id = $bill['delivery_address_id'];
    	$new->charge_reference_value = $bill['charge_reference_value'];
    	$new->pickup_reference_value = $bill['pickup_reference_value'];
    	$new->delivery_reference_value = $bill['delivery_reference_value'];
    	$new->pickup_driver_id = $bill['pickup_driver_id'];
    	$new->delivery_driver_id = $bill['delivery_driver_id'];
    	$new->pickup_driver_percentage = $bill['pickup_driver_percentage'];
    	$new->delivery_driver_percentage = $bill['delivery_driver_percentage'];
    	$new->interliner_id = $bill['interliner_id'];
    	$new->interliner_amount = $bill['interliner_amount'];
    	$new->bill_number = $bill['bill_number'];
    	$new->description = $bill['description'];
    	$new->date = $bill['date'];
    	$new->amount = $bill['amount'];

    	$new = $new->create($bill);

    	return $new;
    }

    public function CountByDriver($driverId) {
	    $bills = \DB::table("bills")->select(\DB::raw('count(bill_id) as bill_count'))
            ->where('pickup_driver_id', '=', $driverId)
            ->orWhere('delivery_driver_id', '=', $driverId)
            ->get();

	    return $bills[0]->bill_count;
    }
}
