<?php
namespace app\Http\Models\Invoice;

class InvoiceTable {
    public $charge_account_id;
    public $charge_account_name;
    public $lines = array();
    public $bill_subtotal = 0;
    public $tax_subtotal;
    public $discount_subtotal;
    public $fuel_surcharge_subtotal;
    public $total_subtotal;
}
?>
