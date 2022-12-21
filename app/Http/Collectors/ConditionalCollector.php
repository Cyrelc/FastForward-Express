<?php

namespace App\Http\Collectors;

class ConditionalCollector {
    public function Collect($req, $conditionalId) {
        return [
            'action' => json_encode($req->action),
            'conditional_id' => $conditionalId,
            'human_readable' => $req->human_readable,
            'json_logic' => json_encode($req->json_logic),
            'name' => $req->name,
            'ratesheet_id' => $req->ratesheet_id,
            'value' => $req->value,
            'value_type' => json_encode($req->value_type)
        ];
    }
}

?>
