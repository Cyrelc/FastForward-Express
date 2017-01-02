<?php
	namespace App\Http\Repos;

	use App\Address;

	class AddressRepo {
		public function GetById($id) {
			$ad = Address::where('address_id', '=', $id)->first();

			return $ad;
		}

        public function Edit($addr) {
            $old = GetById($addr->address_id);

            $old->street = $addr->street;
            $old->street2 = $addr->street2;
            $old->city = $addr->city;
            $old->zip_postal = $addr->zip_postal;
            $old->state_province = $addr->state_province;
            $old->country = $addr->country;
            $old->is_primary = $addr->is_primary;

            $old->save();
        }

        public function Delete($aId){
		    $address = GetById($aId);

		    $address->delete();
        }
	}