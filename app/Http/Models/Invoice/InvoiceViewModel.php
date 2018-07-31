<?php
namespace app\Http\Models\Invoice;

class InvoiceViewModel {
    public $invoice;
    public $parents = array();
    public $bill_count;
    public $tables = array();
    public $amount;
    public $tax;
    public $total;
    public $account_owing = 0;
}

?>
