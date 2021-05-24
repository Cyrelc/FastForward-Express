<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Interliner;

class InterlinerController extends Controller {

    public function buildTable() {
        if($req->user()->cannot('viewAny', Interliner::class))
            abort(403);

        $interlinerRepo = new Repos\InterlinerRepo();
        $interliners = $interlinerRepo->ListAll();

        return json_encode($interliners);
    }

    public function store(Request $req) {
        $interlinerRepo = new Repos\InterlinerRepo();
        $interliner = $interlinerRepo->GetById($req->interliner_id);
        if($req->interliner_id ? $req->user()->cannot('update', $interliner) : $req->user()->cannot('create', Interliner::class))
            abort(403);

        DB::beginTransaction();
        $interlinerValidation = new \App\Http\Validation\InterlinerValidationRules();
        $temp = $interlinerValidation->GetValidationRules($req);
        $this->validate($req, $temp['rules'], $temp['messages']);

        $addrCollector = new \App\Http\Collectors\AddressCollector();
        $interlinerCollector = new \App\Http\Collectors\InterlinerCollector();
        $addrRepo = new Repos\AddressRepo();

        $address = $addrCollector->CollectMinimal($req, 'address');
        if ($req->address_id)
            $addressId = $addrRepo->Update($address)->address_id;
        else
            $addressId = $addrRepo->InsertMinimal($address)->address_id;

        $interliner = $interlinerCollector->Collect($req, $addressId);

        if ($req->interliner_id)
            $interliner = $interlinerRepo->Update($interliner);
        else
            $interliner = $interlinerRepo->Insert($interliner);

        DB::commit();
        return response()->json([
            'success' => true,
            'interliners' => $interlinerRepo->ListAll()
        ]);
    }
}

?>
