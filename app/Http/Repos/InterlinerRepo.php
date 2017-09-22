<?php
namespace App\Http\Repos;

use App\Interliner;

class InterlinerRepo {

    public function ListAll() {
        $interliners = Interliner::All();

        return $interliners;
    }

    public function GetById($id) {
	    $interliner = Interliner::where('interliner_id', '=', $id)->first();

	    return $interliner;
    }
}