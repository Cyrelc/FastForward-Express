<?php
namespace App\Http\Validation;

class AccountValidationRules {
    public function GetValidationRules($req, $accountPermissionsObject) {
        $advancedRules = [
            'account_number' => 'required|unique:accounts,account_number,' . $req->account_id . ',account_id',
            'parent_account_id' => 'exists:accounts,account_id',
            'discount' => 'nullable|numeric|between:0,100',
            'min_invoice_amount' => 'nullable|numeric',
            'start_date' => 'required|date',
        ];
        $basicRules = [
            'account_name' => 'required|unique:accounts,name,' . $req->account_id . ',account_id',
        ];
        $invoicingRules = [
            'invoice_interval' => 'required|exists:selections,value',
        ];
        $rules = array_merge(
            $accountPermissionsObject['editAdvanced'] ? $advancedRules : [],
            $accountPermissionsObject['editBasic'] ? $basicRules : [],
            $accountPermissionsObject['editInvoicing'] ? $invoicingRules : []
        );

        $advancedMessages = [
            'account_number.required' => 'Account Number is required',
            'account_number.unique' => 'Account Number must be unique',
            'discount.numeric' => 'Discount must be a number',
            'discount.between' => 'Discount value is invalid'
        ];
        $basicMessages = [
            'account_name.required' => 'Company Name is required.',
            'account_name.unique' => 'That account name is already taken - please try again'
        ];
        $invoicingMessages = [

        ];
        $messages = array_merge(
            $accountPermissionsObject['editAdvanced'] ? $advancedMessages : [],
            $accountPermissionsObject['editBasic'] ? $basicMessages : [],
            $accountPermissionsObject['editInvoicing'] ? $invoicingMessages : []
        );
        return ['rules' => $rules, 'messages' => $messages];
    }

    public function GetAccountCreditRules($req) {
        $rules = [
            'bill_id' => 'required|numeric|exists:bills,bill_id',
            'account_id' => 'required|exists:accounts,account_id',
            'credit_amount' => 'required|numeric'
        ];
        $messages = [
            'bill_id.required' => 'You must credit against a bill id',
            'bill_id.exists' => 'Invalid bill id entered',
            'account_id.exists' => 'Invalid account id',
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }
}

