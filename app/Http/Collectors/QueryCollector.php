<?php

namespace App\Http\Collectors;

class QueryCollector {
    public function Collect($req) {
        return [
            'name' => $req->name,
            'query_string' => $req->query_string,
            'table' => $req->table,
            'user_id' => $req->user()->id
        ];
    }
}


