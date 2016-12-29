<?php
	namespace App\Http\Repos;

	use App\Address;

	class AddressRepo {
		public function GetById($id) {
			$ad = Address::where('address_id', '=', $id)->first();

			return $ad;
		}
	}