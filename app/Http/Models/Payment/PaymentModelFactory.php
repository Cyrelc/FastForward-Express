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
        $model->outstanding_invoice_count = count($invoiceRepo->GetOutstandingByAccountId($accountId));

        return $model;
    }

    public function GetReceivePaymentModel($accountId, $creditCardsOnFile) {
        $paymentRepo = new Repos\PaymentRepo();
        $invoiceRepo = new Repos\InvoiceRepo();

        $model = new \stdClass();

        $paymentTypes = $paymentRepo->GetPaymentTypesForAccounts();

        $model->payment_types = array();

        foreach($creditCardsOnFile as $cc)
            $model->payment_types[] = [
                'name' => $cc->masked_pan,
                'is_prepaid' => true,
                'cc_on_file' => true,
                'required_field' => null,
                'credit_card_id' => $cc->credit_card_id,
                'payment_type_id' => $cc->payment_type_id
            ];

        foreach($paymentTypes as $paymentType)
            $model->payment_types[] = $paymentType;

        $model->outstanding_invoices = $invoiceRepo->GetOutstandingByAccountId($accountId);

        return $model;
    }
}

?>
