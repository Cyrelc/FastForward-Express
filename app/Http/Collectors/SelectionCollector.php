<?php

namespace App\Http\Collectors;


class SelectionCollector {
    public function Collect($req) {
        return [
            'name' => $req->name,
            'type' => $req->type,
            'value' => $req->value,
        ];
    }
}
