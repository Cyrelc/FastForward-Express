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

    public function GetByInvoiceId($id) {
        $bills = Bill::where('invoice_id', '=', $id)->get();

        return $bills;
    }

    public function CheckIfInvoiced($id) {
        $bill = Bill::where('bill_id', '=', $id)->first();
        
        return ($bill->is_invoiced);
    }

    public function CheckIfManifested($id) {
        $bill = Bill::where('bill_id', '=', $id)->first();

        if($bill->is_pickup_manifested)
            return true;
        else if($bill->is_delivery_manifested)
            return true;
        else
            return false;
    }

    public function Insert($bill) {
    	$new = new Bill;

    	return ($new->create($bill));
    }

    public function Delete($id) {
        $bill = $this->GetById($id);

        $bill->delete();
        return;
    }

    public function Update($bill) {
        $old = $this->GetById($bill['bill_id']);

        $old->charge_account_id = $bill['charge_account_id'];
        $old->pickup_account_id = $bill['pickup_account_id'];
        $old->delivery_account_id = $bill['delivery_account_id'];
        $old->pickup_address_id = $bill['pickup_address_id'];
        $old->delivery_address_id = $bill['delivery_address_id'];
        $old->charge_reference_value = $bill['charge_reference_value'];
        $old->pickup_reference_value = $bill['pickup_reference_value'];
        $old->delivery_reference_value = $bill['delivery_reference_value'];
        $old->pickup_driver_id = $bill['pickup_driver_id'];
        $old->delivery_driver_id = $bill['delivery_driver_id'];
        $old->pickup_driver_commission = $bill['pickup_driver_commission'];
        $old->delivery_driver_commission = $bill['delivery_driver_commission'];
        $old->interliner_id = $bill['interliner_id'];
        $old->interliner_amount = $bill['interliner_amount'];
        $old->skip_invoicing = $bill['skip_invoicing'];
        $old->bill_number = $bill['bill_number'];
        $old->description = $bill['description'];
        $old->date = $bill['date'];
        $old->amount = $bill['amount'];
        $old->delivery_type = $bill['delivery_type'];

        $old->save();

        return $old;
    }

    public function CountByInvoiceId($invoiceId) {
        $bills = \DB::table("bills")->select(\DB::raw('count(bill_id) as bill_count'))
            ->where('invoice_id', '=', $invoiceId)
            ->get();

        return $bills[0]->bill_count;
    }

    public function CountByDriver($driverId) {
	    $bills = \DB::table("bills")->select(\DB::raw('count(bill_id) as bill_count'))
            ->where('pickup_driver_id', '=', $driverId)
            ->orWhere('delivery_driver_id', '=', $driverId)
            ->get();

	    return $bills[0]->bill_count;
    }
}
