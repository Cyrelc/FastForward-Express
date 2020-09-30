<?php
namespace App\Http\Repos;

use App\Interliner;

class InterlinerRepo {

    public function ListAll() {
        $interliners = Interliner::leftjoin('addresses', 'addresses.address_id', '=', 'interliners.address_id')
        ->select(
            'addresses.name as address_name',
            'interliner_id',
            'interliners.name as interliner_name',
            'addresses.formatted as formatted'
        );

        return $interliners->get();
    }

    public function GetById($id) {
	    $interliner = Interliner::where('interliner_id', '=', $id)->first();

	    return $interliner;
    }

    public function GetInterlinersList() {
        $interliners = Interliner::select('name as label', 'interliner_id as value');

        return $interliners->get();
    }

    public function Insert($interliner) {
    	$new = new Interliner;

    	return ($new->create($interliner));
    }

    public function Update($interliner) {
        $old = $this->GetById($interliner['interliner_id']);

        $old->name = $interliner['name'];
        $old->address_id = $interliner['address_id'];

        $old->save();

        return $old;
    }
}
