<?php
namespace App\Http\Validation;

use App\Models\Ratesheet;
use App\Http\Repos;
use Illuminate\Validation\Rule;

class TimingConditionalValidationRules {
    public function GetValidationRules($req, $timingConditionalId) {
        $timingConditionalRepo = new Repos\TimingConditionalRepo();
        $oldTimingConditional = $timingConditionalId ? $timingConditionalRepo->GetById($timingConditionalId) : null;

        $rules = [
            'action' => 'required',
            'continue_on' => 'required|boolean',
            'human_readable' => 'required',
            'json_logic' => 'required|json',
            'name' => [
                'required',
                Rule::unique('timing_conditionals')->where(function($query) use ($req) {
                    return $query->where('ratesheet_id', $req->ratesheet_id)
                        ->where('name', $req->name);
                })->ignore($oldTimingConditional),
            ],
            'priority' => ['numeric'],
            'ratesheet_id' => [
                Rule::exists(Ratesheet::class, 'ratesheet_id'),
                'required',
            ],
            'value' => [$req->value_type == 'field' ? 'string' : 'numeric', 'required'],
            'value_type' => 'required'
        ];

        $messages = [

        ];

        return ['rules' => $rules, 'messages' => $messages];
    }
}
