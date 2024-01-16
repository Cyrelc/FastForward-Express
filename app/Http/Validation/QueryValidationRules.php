<?php
namespace App\Http\Validation;


class QueryValidationRules {
    public function GetValidationRules($req) {
        $rules = [
            'name' => 'required',
            'query_string' => 'required',
            'table' => 'required',
        ];

        $messages = [

        ];

        return ['rules' => $rules, 'messages' => $messages];
    }    
}

