<?php

namespace App\Http\Collectors;

class ConditionalCollector {
    public function Collect($req, $conditionalId) {
        return [
            'action' => json_encode($req->action),
            'conditional_id' => $conditionalId,
            'equation_string' => $req->value_type == 'equation' ? $req->equation_string : null,
            'human_readable' => $req->human_readable,
            'json_logic' => json_encode($req->json_logic),
            'name' => $req->name,
            'ratesheet_id' => $req->ratesheet_id,
            'value' => $req->value_type == 'equation' ? null : $req->value,
            'value_type' => $req->value_type
        ];
    }
}

?>
