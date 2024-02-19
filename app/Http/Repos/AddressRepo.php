<?php
namespace App\Http\Repos;

use App\Models\Address;

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

        foreach((new Address())->getFillable() as $field)
            if(isset($address[$field]))
                $old[$field] = $address[$field];

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
