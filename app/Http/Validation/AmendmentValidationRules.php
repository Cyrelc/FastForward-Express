<?php
namespace App\Http\Validation;

class AmendmentValidationRules {
    public function GetValidationRules($req) {
        $rules = [
            'amount' => 'required|numeric|not_in:[0]',
            'bill_id' => 'required|exists:bills,bill_id',
            'description' => 'required|string|min:15',
            'invoice_id' => 'required|exists:invoices,invoice_id'
        ];
        $messages = [
            'amount.required' => 'Please enter a valid amount. Enter a negative number to credit the invoice, or a positive one to increase a price',
            'bill_id.required' => 'All amendments must be against a bill',
            'bill_id.exists' => 'Requested bill does not exist',
            'description.min' => 'Description field must be at least 15 characters in length. Please indicate the reason for this amendment',
            'description.required' => 'Description field must be at least 15 characters in length. Please indicate the reason for this amendment',
            'invoice_id.required' => 'Invoice Id invalid - please contact support',
            'invoice_id.exists' => 'Invoice Id invalid - please contact support'
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }

}

