<?php

namespace App\Http\Collectors;


class InterlinerCollector {
	public function Collect($req, $addressId) { 
		return [
		'name' => $req->input('name'),
		'address_id' => $addressId 
		];
	}
}
?>
