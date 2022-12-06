<?php

namespace App\Http\Controllers;

// use App\Http\Models;
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
    
                switch($classIdentifier) {
                    case 'A':
                        $searchResults['accounts'] = array_merge($searchResults['accounts'], $searchRepo->AccountSearch($objectId));
                        break;
                    case 'B':
                        $searchResults['bills'] = array_merge($searchResults['bills'], $searchRepo->BillSearch($objectId));
                        break;
                    case 'E':
                        $searchResults['employees'] = array_merge($searchResults['employees'], $searchRepo->EmployeeSearch($objectId));
                        break;
                    case 'I':
                        $searchResults['invoices'] = array_merge($searchResults['invoices'], $searchRepo->InvoiceSearch($objectId));
                        break;
                    case 'M':
                        $searchResults['manifests'] = array_merge($searchResults['manifests'], $searchRepo->ManifestSearch($objectId));
                        break;
                }
            } else
                $searchResults = array_merge($searchResults, $searchRepo->GlobalSearch($searchTerm));
        }

        $result = [];

        foreach($searchResults as $resultType)
            $result = array_merge($result, $resultType);

        return $result;
    }
}

?>
