<?php
namespace App\Http\Collectors;

class AddressCollector {
    public function Collect($req, $prefix, $isPrimary) {
        return [
            'name'=>$req->input($prefix . '-name'),
            'address_id'=>$req->input($prefix . '-id'),
            'street'=>$req->input($prefix . '-street'),
            'street2'=>$req->input($prefix . '-street2'),
            'city'=>$req->input($prefix . '-city'),
            'zip_postal'=>$req->input($prefix . '-zip-postal'),
            'state_province'=>$req->input($prefix . '-state-province'),
            'country'=>$req->input($prefix . '-country'),
            'is_primary'=>$isPrimary
        ];
    }

    public function Remerge($req, $address, $prefix) {
        if ($req->old($prefix . "-name") !== null)
            $address->name = $req->old($prefix . "-name");

        if ($req->old($prefix . "-street") !== null)
            $address->street = $req->old($prefix . "-street");

        if ($req->old($prefix . "-street2") !== null)
            $address->street2 = $req->old($prefix . "-street2");

        if ($req->old($prefix . "-city") !== null)
            $address->city = $req->old($prefix . "-city");

        if ($req->old($prefix . "-zip-postal") !== null)
            $address->zip_postal = $req->old($prefix . "-zip-postal");

        if ($req->old($prefix . "-state-province") !== null)
            $address->state_province = $req->old($prefix . "-state-province");

        if ($req->old($prefix . "-country") !== null)
            $address->country = $req->old($prefix . "-country");

        return $address;
    }

    public function ToObject($array) {
        $addr = new \App\Address();

        $addr->name = $array['name'];
        $addr->street = $array['street'];
        $addr->street2 = $array['street2'];
        $addr->city = $array['city'];
        $addr->zip_postal = $array['zip_postal'];
        $addr->state_province = $array['state_province'];
        $addr->country = $array['country'];

        return $addr;
    }

    public function ToArray($object, $is_primary) {
        return [
            'name' => $object->name,
            'street' => $object->street,
            'street2' => $object->street2,
            'city' => $object->city,
            'zip_postal' => $object->zip_postal,
            'state_province' => $object->state_province,
            'country' => $object->country,
            'is_primary' => $is_primary
        ];
    }
}
