<?php
namespace App\Http\Validation;

class PaymentValidationRules {
    public function GetPaymentOnAccountRules($req, $invoices) {
        $rules = [
            'account-id' => 'required',
            'select_payment' => 'required',
        ];

        $messages = [
            'account-id.required' => 'Account Id invalid. Please try again',
            'select_payment.required' => 'Please select a valid payment method',
        ];

        $invoice_total = 0;

        foreach($invoices as $invoice) {
            $invoice_total += $req->input($invoice->invoice_id . '_payment_amount');
            $rules = array_merge($rules, [$invoice->invoice_id . '_payment_amount' => 'numeric|min:0|max:' . $invoice->balance_owing]);
            $messages = array_merge($messages, [$invoice->invoice_id . '_payment_amount.max' => 'Maximum payment on invoice ' . $invoice->invoice_id . ' cannot exceed outstanding balance']);
        }

        $rules = array_merge($rules, ['payment_amount' => 'required|numeric|min:' . $invoice_total]);
        $messages = array_merge($messages, ['payment_amount.min' => 'Payment amount must match or exceed invoice total payments']);

        if($req->select_payment == 'cheque' || $req->select_payment == 'bank_transfer' || $req->select_payment == 'credit_card') {
            $rules = array_merge($rules, ['reference_value' => 'required']);
            $messages = array_merge($messages, ['reference_value.required' => 'This type of payment method requires a reference value']);
        }

        return ['rules' => $rules, 'messages' => $messages];
    }
}

?>
