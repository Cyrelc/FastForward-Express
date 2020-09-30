<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Interliner;

class InterlinerController extends Controller {

    public function buildTable() {
        $interlinerRepo = new Repos\InterlinerRepo();
        $interliners = $interlinerRepo->ListAll();

        return json_encode($interliners);
    }

    public function create(Request $req) {
        // Check permissions
        $interliner_model_factory = new Interliner\InterlinerModelFactory();
        $model = $interliner_model_factory->GetCreateModel($req);
        return view('interliners.interliner', compact('model'));
    }

    public function edit(Request $req, $id) {
        $factory = new Interliner\InterlinerModelFactory();
        $model = $factory->GetEditModel($req, $id);
        return view('interliners.interliner', compact('model'));
    }

    public function store(Request $req) {
    	//TODO - does not handle edit
    	//TODO - check permissions
        DB::beginTransaction();
        try {
            $interlinerValidation = new \App\Http\Validation\InterlinerValidationRules();
            $temp = $interlinerValidation->GetValidationRules($req);

            $validationRules = $temp['rules'];
            $validationMessages = $temp['messages'];

            $addrCollector = new \App\Http\Collectors\AddressCollector();
            $interlinerCollector = new \App\Http\Collectors\InterlinerCollector();
            $addrRepo = new Repos\AddressRepo();
            $interlinerRepo = new Repos\InterlinerRepo();

            $address = $addrCollector->CollectForAccount($req, 'address');
            if ($req->address_id) 
                $addressId = $addrRepo->Update($address)->address_id;
            else
                $addressId = $addrRepo->Insert($address)->address_id;

            $interliner = $interlinerCollector->Collect($req, $addressId);

            if ($req->interliner_id) {
                $interliner = $interlinerRepo->Update($interliner);
                DB::commit();
                return redirect()->action('InterlinerController@index');
            }
            else {
                $interliner = $interlinerRepo->Insert($interliner);
                DB::commit();
                return redirect()->action('InterlinerController@create');
            }
        } catch(Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

?>
