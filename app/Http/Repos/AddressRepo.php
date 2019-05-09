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

    public function Insert($address) {
        $new = new Address;

        $new->name = $address['name'];
        $new->street = $address['street'];
        $new->street2 = $address['street2'];
        $new->city = $address['city'];
        $new->zip_postal = $address['zip_postal'];
        $new->state_province = $address['state_province'];
        $new->country = $address['country'];
        $new->is_primary = $address['is_primary'];
        $new->lat = $address['lat'];
        $new->lng = $address['lng'];
        $new->formatted = $address['formatted'];

        if (array_key_exists('contact_id', $address))
            $new->contact_id = $address['contact_id'];

        $new = $new->create($address);

        return $new;
    }

    public function Update($address) {
        $old = $this->GetById($address['address_id']);

        $old->name = $address['name'];
        $old->street = $address['street'];
        $old->street2 = $address['street2'];
        $old->city = $address['city'];
        $old->zip_postal = $address['zip_postal'];
        $old->state_province = $address['state_province'];
        $old->country = $address['country'];
        $old->lat = $address['lat'];
        $old->lng = $address['lng'];
        $old->formatted = $address['formatted'];

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
