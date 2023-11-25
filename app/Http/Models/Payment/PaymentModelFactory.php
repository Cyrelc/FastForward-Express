<?php
namespace App\Http\Models\Payment;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Payment;

class PaymentModelFactory {
    public function GetAccountStripePaymentMethods($account) {
        return $this->GetStripePaymentMethods($account);
    }

    public function GetReceivePaymentModel($invoice) {
        $accountRepo = new Repos\AccountRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $model = new \stdClass();
        $model->payment_methods = Array();

        // TODO: Prepaid types should only show up if user is administrator (system user, not account user)
        // and has permission to pay off invoices without a transaction
        if($invoice->account_id) {
            $account = $accountRepo->GetById($invoice->account_id);

            $model->payment_methods['prepaid'] = $paymentRepo->GetPrepaidPaymentTypes()->toArray();
            $model->payment_methods['cards_on_file'] = $this->GetStripePaymentMethods($account);
            $model->payment_methods['account'] = $paymentRepo->GetPaymentTypeByName('Account')->toArray();
            $model->payment_methods['account']['account_balance'] = $account->account_balance;
            $model->payment_methods['stripe_pending'] = $paymentRepo->GetPaymentTypeByName('Stripe (Pending)');
        } else {
            $model->payment_methods['prepaid'] = $paymentRepo->GetPrepaidPaymentTypes();
            $model->payment_methods['stripe_pending'] = $paymentRepo->GetPaymentTypeByName('Stripe (Pending)');
        }

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
                'last_four' => $paymentMethod->card->last4,
                'name' => '**** **** **** ' . $paymentMethod->card->last4,
                'payment_method_id' => $paymentMethod->id,
                'payment_method_on_file' => true,
                'required_field' => null,
                'type' => 'card_on_file'
            ];
        }

        return $result;
    }
}

?>
