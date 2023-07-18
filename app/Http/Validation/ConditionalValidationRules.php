<?php
namespace App\Http\Validation;

use App\Ratesheet;
use App\Http\Repos;
use Illuminate\Validation\Rule;

class ConditionalValidationRules {
    public function GetValidationRules($req, $conditionalId) {
        $conditionalRepo = new Repos\ConditionalRepo();
        $oldConditional = $conditionalId ? $conditionalRepo->GetById($conditionalId) : null;

        $rules = [
            'action' => 'required',
            'equation_string' => [
                Rule::requiredIf($req->value_type == 'equation')
            ],
            'human_readable' => 'required',
            'json_logic' => 'required',
            'name' => [
                'required',
                Rule::unique('conditionals')->where(function($query) use ($req) {
                    return $query->where('ratesheet_id', $req->ratesheet_id)
                        ->where('name', $req->name);
                })->ignore($oldConditional),
            ],
            'original_equation_string' => [
                Rule::requiredIf($req->value_type == 'equation')
            ],
            'ratesheet_id' => [
                Rule::exists(Ratesheet::class, 'ratesheet_id'),
                'required',
            ],
            'value' => ['required', 'numeric'],
            'value_type' => 'required'
        ];

        $messages = [

        ];

        return ['rules' => $rules, 'messages' => $messages];
    }
}


