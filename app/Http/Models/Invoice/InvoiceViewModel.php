<?php
namespace app\Http\Models\Invoice;

class InvoiceViewModel {
    public $invoice;
    public $account;
    public $parents = array();
    public $bill_count;
    public $headers = array();
    public $table = array();
    public $amount;
    public $tax;
    public $total;
}

?>
