<?php
namespace App\Http\Validation;

class AdminValidationRules {
    public function GetPaymentTypeValidationRules($req) {
        $rules = [
            'paymentTypes.*.payment_type_id' => 'required|integer|min:1',
            'paymentTypes.*.default_ratesheet_id' => 'required|integer|exists:ratesheets,ratesheet_id'
        ];
        $messages = [
            'packages.*.default_ratesheet_id.exists' => 'Ratesheet must exist in database. Please select a valid ratesheet'
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }
}

?>
