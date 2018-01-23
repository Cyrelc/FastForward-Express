<?php
namespace app\Http\Models\Invoice;

class InvoiceLine {
    public $is_subtotal = false;
    public $date;
    public $bill_number;
    public $delivery_address_name;
    public $pickup_address_name;
    public $amount;
}

?>
