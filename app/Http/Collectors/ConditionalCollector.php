<?php

namespace App\Http\Collectors;

class ConditionalCollector {
    public function Collect($req, $conditionalId) {
        return [
            'action' => json_encode($req->action),
            'conditional_id' => $conditionalId,
            'human_readable' => $req->human_readable,
            'json_logic' => $req->json_logic,
            'name' => $req->name,
            'original_equation_string' => $req->value_type == 'equation' ? $req->equation_string : null,
            'priority' => $req->priority,
            'ratesheet_id' => $req->ratesheet_id,
            'type' => $req->type,
            'value_type' => $req->value_type,
            'value' => $req->value_type == 'equation' ? null : $req->value,
        ];
    }
}

?>
