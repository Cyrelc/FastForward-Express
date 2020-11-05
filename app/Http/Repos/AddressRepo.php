<?php
namespace App\Http\Repos;

use App\Address;

class AddressRepo {
    public function ListByContactId($id) {
        $ad = Address::where('contact_id', '=', $id)->get();
        return $ad;
    }

    public function GetByContactId($id) {
        $ad = Address::where('contact_id', '=', $id)
            ->where('is_primary', '=', 1)
            ->first();

        return $ad;
    }

    public function GetById($id) {
        $ad = Address::where('address_id', '=', $id)->first();

        return $ad;
    }

    public function InsertMinimal($address) {
        $new = new Address;

        return $new->create($address);
    }

    public function updateMinimal($address) {
        $old = $this->GetById($address['address_id']);

        $old->name = $address['name'];
        $old->formatted = $address['formatted'];
        $old->lat = $address['lat'];
        $old->lng = $address['lng'];
        $old->place_id = $address['place_id'];

        if (array_key_exists('contact_id', $address))
            $old->contact_id = $address['contact_id'];

        $old->save();

        return $old;
    }

    public function Delete($aId){
        $address = $this->GetById($aId);

        $address->delete();
    }

    public function DeleteByContact($cid) {
        $addrs = $this->ListByContactId($cid);
        if (!isset($addrs)) return;

        foreach($addrs as $addr) {
            $this->Delete($addr->address_id);
        }
    }
}
