<?php

namespace App\Http\Controllers;

use App\Models\Query;
use App\Http\Collectors;
use App\Http\Repos;
use App\Http\Validation;

use Illuminate\Http\Request;

use DB;

class QueryController extends Controller {
    public function deleteQuery(Request $req, $queryId) {
        $queryRepo = new Repos\QueryRepo();
        $query = $queryRepo->GetById($queryId);

        if($req->user()->cannot('delete', $query))
            return(403);

        $queryRepo->Delete($query->id);
        
        return $queryRepo->GetByTable($query->table);
    }

    public function StoreQuery(Request $req) {
        $queryValidationRules = new Validation\QueryValidationRules();

        $queryRules = $queryValidationRules->GetValidationRules($req);
        $this->validate($req, $queryRules['rules'], $queryRules['messages']);

        $queryCollector = new Collectors\QueryCollector();
        $query = $queryCollector->Collect($req);

        $queryRepo = new Repos\QueryRepo();
        DB::beginTransaction();

        $queryRepo->Insert($query);

        DB::commit();

        return $queryRepo->GetByTable($query['table']);
    }
}

?>
