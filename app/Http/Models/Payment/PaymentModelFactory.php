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

            $model->payment_methods[] = ['label' => 'Card On File', 'options' => $this->GetStripePaymentMethods($account)];
            if($account->account_balance > 0) {
                $accountPaymentMethod = $paymentRepo->GetPaymentTypeByName('Account');
                $accountPaymentMethod->account_balance = $account->account_balance;
                $model->payment_methods[] = ['label' => 'Account', 'options' => [$accountPaymentMethod]];
            }
            $model->payment_methods[] = ['label' => 'Card Not On File', 'options' => [$paymentRepo->GetPaymentTypeByName('Stripe (Pending)')]];
            $model->payment_methods[] = ['label' => 'Prepaid', 'options' => $paymentRepo->GetPrepaidPaymentTypes()];
        } else {
            $model->payment_methods[] = ['label' => 'Card Not On File', 'options' => [$paymentRepo->GetPaymentTypeByName('Stripe (Pending)')]];
            $model->payment_methods[] = ['label' => 'Prepaid', 'options' => $paymentRepo->GetPrepaidPaymentTypes()];
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
        $paymentType = $paymentRepo->GetPaymentTypeByName($card->brand);
        if(!$paymentType)
            $paymentType = $paymentRepo->GetPaymentTypeByName('Stripe (Pending)');

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
                'payment_type_id' => $paymentType->payment_type_id,
                'required_field' => null,
                'type' => 'card_on_file'
            ];
        }

        return $result;
    }
}

?>
