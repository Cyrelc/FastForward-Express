<?php
namespace App\Http\Validation;

use App\Models\Ratesheet;
use App\Http\Repos;
use Illuminate\Validation\Rule;

class ConditionalValidationRules {
    public function GetValidationRules($req, $conditionalId) {
        $conditionalRepo = new Repos\ConditionalRepo();
        $oldConditional = $conditionalId ? $conditionalRepo->GetById($conditionalId) : null;

        // A refusal ("we cannot carry this — call us") produces a message rather than a charge, so the
        // value is text and there is no charge type to give it.
        $isRefusal = $req->value_type === 'message';

        $rules = [
            'action' => 'required',
            'human_readable' => 'required',
            'json_logic' => 'required|json',
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
            // 0 is a legitimate priority, and it is the default for a refusal - it runs before
            // everything, so it belongs at the front of the list.
            'priority' => ['numeric', 'min:0'],
            'ratesheet_id' => [
                Rule::exists(Ratesheet::class, 'ratesheet_id'),
                'required',
            ],
            // A refusal produces no charge, so it has no charge type to categorise.
            'type' => $isRefusal
                ? ['nullable', 'numeric', 'exists:selections,selection_id']
                : ['required', 'numeric', 'exists:selections,selection_id'],
            // 'message' rules store the text shown to staff when the trip is refused; 'field' rules
            // store a context path (e.g. pickup_zone_selected_type_cost); everything else is a number.
            'value' => $isRefusal || $req->value_type === 'field'
                ? ['required', 'string']
                : ['required', 'numeric'],
            'value_type' => 'required'
        ];

        $messages = [

        ];

        return ['rules' => $rules, 'messages' => $messages];
    }
}


