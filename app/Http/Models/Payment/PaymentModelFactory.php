<?php
namespace App\Http\Models\Payment;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Payment;

class PaymentModelFactory {
    public function GetModelByAccountId($accountId) {
        $paymentRepo = new Repos\PaymentRepo();
        $invoiceRepo = new Repos\InvoiceRepo();

        $model = new \stdClass();
        $model->payments = $paymentRepo->GetPaymentsByAccountId($accountId);
        $model->payment_types = $paymentRepo->GetPaymentTypesForAccounts();
        $model->outstanding_invoices = $invoiceRepo->GetOutstandingByAccountId($accountId);

        return $model;
    }
}


?>
