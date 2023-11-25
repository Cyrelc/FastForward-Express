<?php
namespace App\Http\Validation;

use App\Http\Repos;

class PaymentValidationRules {
    public function GetPaymentIntentRules($req) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($req->invoice_id);

        $rules = [
            'invoice_id' => 'required|exists:invoices,invoice_id',
            'amount' => 'required|numeric|gt:0|lte:' . $invoice->balance_owing,
        ];

        $messages = [];

        return [
            'rules' => $rules,
            'messages' => $messages
        ];
    }

    public function GetAccountCreditPaymentRules($req, $invoice) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($invoice->account_id);
        $greaterOf = $account->account_balance > $invoice->balance_owing ? $invoice->balance_owing : $account->account_balance;
        $rules = [
            'amount' => 'required|numeric|between:0,' . floatval(str_replace(',', '', $greaterOf)),
        ];

        $messages = [];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public function GetPrepaidRules($req, $invoice) {
        $paymentRepo = new Repos\PaymentRepo();
        $paymentType = $paymentRepo->GetPaymentType($req->payment_method['payment_type_id']);

        $rules = [
            'amount' => 'required|numeric|between:0,' . floatval(str_replace(',', '', $invoice->balance_owing)),
        ];

        if($paymentType->required_field != null)
            $rules = array_merge($rules, ['reference_value' => 'required']);

        $messages = [];

        return ['rules' => $rules, 'messages' => $messages];
    }

    // public function GetPaymentOnAccountRules($req) {
    //     $paymentRepo = new Repos\PaymentRepo();
    //     $accountRepo = new Repos\AccountRepo();

    //     $account = $accountRepo->GetById($req->account_id);
    //     $accountPaymentType = $paymentRepo->GetPaymentTypeByName('Account');

    //     if(filter_var($req->payment_method_on_file, FILTER_VALIDATE_BOOLEAN))
    //         $this->GetStripePaymentMethodValidationRules($req, $account, $rules, $messages);
    //     else
    //         $this->GetStaticPaymentTypeValidationRules($req, $rules, $messages);

    //     $paymentType = $paymentRepo->GetPaymentType($req->payment_type_id);

    //     $invoiceTotal = 0;

    //     foreach($req->outstanding_invoices as $key => $invoice) {
    //         if(!array_key_exists('payment_amount', $invoice))
    //             continue;
    //         $invoiceTotal += floatval($invoice['payment_amount']);
    //         $rules = array_merge($rules, [
    //             'outstanding_invoices.' . $key . '.payment_amount' => 'numeric|between:0,' . floatval(str_replace(',', '', $invoice['balance_owing'])),
    //             'outstanding_invoices.' . $key . '.invoice_id' => 'required|numeric|exists:invoices,invoice_id'
    //         ]);
    //         $messages = array_merge($messages, [$invoice['invoice_id'] . '' . $key . '.between' => 'Payment on invoice ' . $invoice['invoice_id'] . ' cannot exceed outstanding balance']);
    //     }

    //     if($paymentType->payment_type_id === $accountPaymentType->payment_type_id) {
    //         $rules = array_merge($rules, ['payment_amount' => 'required|numeric|between:0,' . floatval(str_replace(',', '', $account->account_balance))]);
    //         $messages = array_merge($rules, ['payment_amount.between' => 'Payment amount cannot exceed account balance']);
    //     } else {
    //         $rules = array_merge($rules, ['payment_amount' => 'required|numeric|min:' . $invoiceTotal]);
    //         $messages = array_merge($messages, ['payment_amount.min' => 'Payment amount must match or exceed invoice total payments']);
    //     }

    //     if($paymentType->required_field != null) {
    //         $rules = array_merge($rules, ['reference_value' => 'required']);
    //         $messages = array_merge($messages, ['reference_value.required' => 'This type of payment method requires a reference value']);
    //     }

    //     return ['rules' => $rules, 'messages' => $messages];
    // }

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

    // private function GetStaticPaymentTypeValidationRules($req, $rules, $messages) {
    //     $rules = array_merge($rules, [
    //         'payment_type_id' => 'required|exists:payment_methods,payment_method_id'
    //     ]);

    //     $messages = array_merge($messages, [
    //         'payment_type_id.required' => 'Please select a valid payment type',
    //         'payment_type_id.exists' => 'Please select a valid payment type'
    //     ]);
    // }
}

?>
