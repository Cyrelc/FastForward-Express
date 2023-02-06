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
            $classIdentifier = null;

            if(preg_match('/[a-zA-Z]\s?+[0-9]+/i', $searchTerm)) {
                $classIdentifier = substr($searchTerm, 0, 1);
                $searchTerm = substr($searchTerm, 1);
            } else if($req->objectType) {
                $classIdentifier = substr($req->objectType, 0, 1);
            }

            if($classIdentifier)
                switch(strtoupper($classIdentifier)) {
                    case 'A':
                        $searchResults = array_merge($searchResults, $searchRepo->AccountSearch($searchTerm));
                        break;
                    case 'B':
                        $searchResults = array_merge($searchResults, $searchRepo->BillSearch($searchTerm));
                        break;
                    case 'E':
                        $searchResults = array_merge($searchResults, $searchRepo->EmployeeSearch($searchTerm));
                        break;
                    case 'I':
                        $searchResults = array_merge($searchResults, $searchRepo->InvoiceSearch($searchTerm));
                        break;
                    case 'M':
                        $searchResults = array_merge($searchResults, $searchRepo->ManifestSearch($searchTerm));
                        break;
                    case 'U':
                        $searchResults = array_merge($searchResults, $searchRepo->AccountUserSearch($searchTerm));
                }
            else
                $searchResults = array_merge($searchResults, $searchRepo->GlobalSearch($searchTerm));
        }

        return $searchResults;
    }
}

?>
