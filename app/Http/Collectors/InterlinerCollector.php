<?php

namespace App\Http\Collectors;


class InterlinerCollector {
	public function Collect($req, $addressId) { 
		return [
		'interliner_id' => $req->interliner_id,
		'name' => $req->input('name'),
		'address_id' => $addressId 
		];
	}
}
?>
