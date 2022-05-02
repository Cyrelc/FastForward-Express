<?php
namespace App\Http\Validation;

use App\Http\Repos;

class PaymentValidationRules {
    public function GetPaymentOnAccountRules($req) {
        $paymentRepo = new Repos\PaymentRepo();
        $accountRepo = new Repos\AccountRepo();

        $account = $accountRepo->GetById($req->account_id);

        $accountPaymentType = $paymentRepo->GetPaymentTypeByName('Account');
        $paymentType = $paymentRepo->GetPaymentType($req->payment_type_id);

        $rules = [
            'account_id' => 'required',
            'payment_type_id' => 'required',
            'credit_card_id' => 'sometimes|required'
        ];

        $messages = [
            'account_id.required' => 'Account Id invalid. Please try again',
            'payment_type_id.required' => 'Please select a valid payment method',
            'credit_card_id.required' => 'Please select a valid payment method'
        ];

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
}

?>
