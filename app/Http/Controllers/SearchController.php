<?php

namespace App\Http\Controllers;

use App\Http\Models;
use App\Http\Repos;
// use App\Http\Requests;
use Illuminate\Http\Request;

class SearchController extends Controller {
    public function Search(Request $req) {
        $searchRepo = new Repos\SearchRepo();
        $searchTerms = explode(';', $req->term);
        $searchResults = [];

        foreach($searchTerms as $searchTerm) {
            if(preg_match('/[a-zA-Z]\s?+[0-9]+/i', $searchTerm)) {
                $classIdentifier = substr($searchTerm, 0, 1);
                $objectId = substr($searchTerm, 1);

                switch(strtoupper($classIdentifier)) {
                    case 'A':
                        $searchResults = array_merge($searchResults, $searchRepo->AccountSearch($objectId));
                        break;
                    case 'B':
                        $searchResults = array_merge($searchResults, $searchRepo->BillSearch($objectId));
                        break;
                    case 'E':
                        $searchResults = array_merge($searchResults, $searchRepo->EmployeeSearch($objectId));
                        break;
                    case 'I':
                        $searchResults = array_merge($searchResults, $searchRepo->InvoiceSearch($objectId));
                        break;
                    case 'M':
                        $searchResults = array_merge($searchResults, $searchRepo->ManifestSearch($objectId));
                        break;
                }
            } else
                $searchResults = array_merge($searchResults, $searchRepo->GlobalSearch($searchTerm));
        }

        return $searchResults;
    }
}

?>
