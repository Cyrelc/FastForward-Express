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

    public function GetAccountStripePaymentMethods($account) {
        return $this->GetStripePaymentMethods($account);
    }

    public function GetReceivePaymentModel($account) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $model = new \stdClass();

        $paymentMethods = $paymentRepo->GetPaymentTypesForAccounts();

        $model->payment_methods = $this->GetStripePaymentMethods($account);

        foreach($paymentMethods as $paymentType)
            $model->payment_methods[] = $paymentType;

        $model->outstanding_invoices = $invoiceRepo->GetOutstandingByAccountId($account->account_id);

        return $model;
    }

    private function GetStripePaymentMethods($account) {
        if(!$account->hasDefaultPaymentMethod() && $account->hasPaymentMethod()) {
            $paymentMethods = $account->paymentMethods();
            $account->updateDefaultPaymentMethod($paymentMethods[0]->id);
        }

        $defaultPaymentMethod = $account->hasDefaultPaymentMethod() ? $account->defaultPaymentMethod() : null;
        $paymentMethods = $account->paymentMethods();

        $result = array();

        foreach($paymentMethods as $paymentMethod) {
            $expiryDate = \DateTime::createFromFormat('Y/m', $paymentMethod->card->exp_year . '/' . $paymentMethod->card->exp_month);

            $result[] = [
                'brand' => $paymentMethod->card->brand,
                'expiry_date' => $expiryDate->format(\DateTime::ATOM),
                'is_default' => $defaultPaymentMethod ? $paymentMethod->id == $defaultPaymentMethod->id : false,
                'is_expired' => new \DateTime() > $expiryDate,
                'is_prepaid' => true,
                'name' => '**** **** **** ' . $paymentMethod->card->last4,
                'payment_method_id' => $paymentMethod->id,
                'payment_method_on_file' => true,
                'required_field' => null,
            ];
        }

        return $result;
    }
}

?>
