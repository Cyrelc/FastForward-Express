<?php
namespace App\Http\Validation;

use App\Http\Repos;

class PaymentValidationRules {
    public function GetPaymentOnAccountRules($req) {
        $paymentRepo = new Repos\PaymentRepo();
        $accountRepo = new Repos\AccountRepo();

        $account = $accountRepo->GetById($req->account_id);
        $accountPaymentType = $paymentRepo->GetPaymentTypeByName('Account');

        $rules = ['account_id' => 'required|exists:accounts,account_id'];
        $messages = [
            'account_id.required' => 'Account Id invalid. Please try again',
            'account_id.exists' => 'Invalid account id. Please try again'
        ];

        if($req->payment_method_on_file)
            $this->GetStripePaymentMethodValidationRules($req, $account, $rules, $messages);
        else
            $this->GetStaticPaymentTypeValidationRules($req, $rules, $messages);

        $paymentType = $paymentRepo->GetPaymentType($req->payment_type_id);

        $invoice_total = 0;

        foreach($req->outstanding_invoices as $key => $invoice) {
            $invoice_total += floatval($invoice['payment_amount']);
            $rules = array_merge($rules, [
                'outstanding_invoices.' . $key . '.payment_amount' => 'numeric|between:0,' . floatval(str_replace(',', '', $invoice['balance_owing'])),
                'outstanding_invoices.' . $key . '.invoice_id' => 'required|numeric|exists:invoices,invoice_id'
            ]);
            $messages = array_merge($messages, [$invoice['invoice_id'] . '' . $key . '.between' => 'Payment on invoice ' . $invoice['invoice_id'] . ' cannot exceed outstanding balance']);
        }

        if($paymentType->payment_type_id === $accountPaymentType->payment_type_id) {
            $rules = array_merge($rules, ['payment_amount' => 'required|numeric|between:0,' . floatval(str_replace(',', '', $account->account_balance))]);
            $messages = array_merge($rules, ['payment_amount.between' => 'Payment amount cannot exceed account balance']);
        } else {
            $rules = array_merge($rules, ['payment_amount' => 'required|numeric|min:' . $invoice_total]);
            $messages = array_merge($messages, ['payment_amount.min' => 'Payment amount must match or exceed invoice total payments']);
        }

        if($paymentType->required_field != null) {
            $rules = array_merge($rules, ['reference_value' => 'required']);
            $messages = array_merge($messages, ['reference_value.required' => 'This type of payment method requires a reference value']);
        }

        return ['rules' => $rules, 'messages' => $messages];
    }

    private function GetStripePaymentMethodValidationRules($req, $account, $rules, $messages) {
        $paymentRepo = new Repos\PaymentRepo();

        $paymentMethod = $account->findPaymentMethod($req->payment_method_id);
        if(!$paymentMethod)
            abort(422, 'Error: Unable to find requested payment method. Has it been removed?');

        $paymentType = $paymentRepo->GetPaymentTypeByName($paymentMethod->card->brand);
        if(!$paymentType)
            abort(422, 'Error: Unable to find requested payment type ' . $paymentMethod->card->brand . ' in our database. Please try another payment method or contact support');
        $req->payment_type_id = $paymentType->payment_type_id;

        $rules = array_merge($rules, ['payment_method_id' => 'required']);

        $messages = array_merge($messages, []);
    }

    private function GetStaticPaymentTypeValidationRules($req, $rules, $messages) {
        $rules = array_merge($rules, [
            'payment_type_id' => 'required|exists:payment_methods,payment_method_id'
        ]);

        $messages = array_merge($messages, [
            'payment_type_id.required' => 'Please select a valid payment type',
            'payment_type_id.exists' => 'Please select a valid payment type'
        ]);
    }
}

?>
