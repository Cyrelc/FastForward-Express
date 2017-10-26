<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Interliner;

class InterlinerController extends Controller {

    public function create(Request $req) {
        // Check permissions
        $interliner_model_factory = new Interliner\InterlinerModelFactory();
        $model = $interliner_model_factory->GetCreateModel($req);
        return view('interliners.interliner', compact('model'));
    }

    public function store(Request $req) {
    	//TODO - does not handle edit
    	//TODO - check permissions
    	$interlinerValidation = new \App\Http\Validation\InterlinerValidationRules();
    	$temp = $interlinerValidation->GetValidationRules($req);

        $validationRules = $temp['rules'];
        $validationMessages = $temp['messages'];

        $addrCollector = new \App\Http\Collectors\AddressCollector();
        $interlinerCollector = new \App\Http\Collectors\InterlinerCollector();
        $addrRepo = new Repos\AddressRepo();
        $interlinerRepo = new Repos\InterlinerRepo();

        $address = $addrCollector->CollectForAccount($req, 'address', false);
        $addressId = $addrRepo->Insert($address)->address_id;

        $interliner = $interlinerCollector->Collect($req, $addressId);
        $interliner = $interlinerRepo->Insert($interliner);

        return redirect()->action('InterlinerController@create');
    }
}

?>
