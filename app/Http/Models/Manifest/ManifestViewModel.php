<?php
namespace App\Http\Models\Manifest;

class ManifestViewModel {
    public $manifest;
    public $bill_count;
    public $driver_contact;
    public $bill_total = 0.00;
    public $driver_total = 0.00;
    public $chargeback_total = 0.00;
    public $driver_income = 0.00;
    public $bills;
    public $overview;
}

?>
