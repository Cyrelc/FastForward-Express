<?php

namespace App\Http\Repos;

use App\Query;
use Illuminate\Support\Facades\Auth;

class QueryRepo {
    public function Delete($queryId) {
        Query::where('id', $queryId)->delete();
    }

    public function GetById($queryId) {
        return Query::where('id', $queryId)->first();
    }

    public function GetByTable($table) {
        $queries = Query::where('table', $table);

        return $queries->get();
    }

    public function Insert($query) {
        $new = new Query;

        $new->insert($query);
        return $new;
    }
}

