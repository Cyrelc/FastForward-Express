<?php

namespace App\Http\Collectors;

class TimingConditionalCollector {
    public function Collect($req, $timingConditionalId) {
        $continueOn = isset($req->continue_on) ? filter_var($req->continue_on, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : true;
        if($continueOn === null)
            $continueOn = (int) $req->continue_on === 1;

        return [
            'action' => json_encode($req->action),
            'continue_on' => $continueOn ? 1 : 0,
            'human_readable' => $req->human_readable,
            'json_logic' => $req->json_logic,
            'name' => $req->name,
            'priority' => $req->priority,
            'ratesheet_id' => $req->ratesheet_id,
            'timing_conditional_id' => $timingConditionalId,
            'value_type' => $req->value_type,
            'value' => $req->value,
        ];
    }
}

?>
