<?php
namespace App\Http\Collectors;

class AddressCollector {
    public function CollectForAccount($req, $prefix) {
        return [
            'address_id'=>$req->input($prefix . '-id'),
            'name'=>$req->input($prefix . '-name'),
            'street'=>$req->input($prefix . '-street'),
            'street2'=>$req->input($prefix . '-street2'),
            'city'=>$req->input($prefix . '-city'),
            'zip_postal'=>$req->input($prefix . '-zip-postal'),
            'state_province'=>$req->input($prefix . '-state-province'),
            'country'=>$req->input($prefix . '-country'),
            'is_primary'=>false,
            'contact_id'=>null,
            'lat'=>$req->input($prefix . '-lat') == 0 ? null : $req->input($prefix . '-lat'),
            'lng'=>$req->input($prefix . '-lng') == 0 ? null : $req->input($prefix . '-lng'),
            'formatted'=>$req->input($prefix . '-formatted') == '' ? join(' ', array($req->input($prefix . '-name'), $req->input($prefix . '-street'), $req->input($prefix . '-street2'), $req->input($prefix . '-city'), $req->input($prefix . '-state-province'), $req->input($prefix . '-country'), $req->input($prefix . '-zip-postal'))) : $req->input($prefix . '-formatted')
        ];
    }
    
    public function Collect($req, $prefix, $isPrimary, $newId = null) {
        $prefix = $prefix . '-address';
        return [
            'address_id'=>$req->input($prefix . '-id'),
            'name'=>$req->input($prefix . '-name'),
            'street'=>$req->input($prefix . '-street'),
            'street2'=>$req->input($prefix . '-street2'),
            'city'=>$req->input($prefix . '-city'),
            'zip_postal'=>$req->input($prefix . '-zip-postal'),
            'state_province'=>$req->input($prefix . '-state-province'),
            'country'=>$req->input($prefix . '-country'),
            'is_primary'=>$isPrimary,
        ];
    }

    public function CollectMinimal($req, $prefix, $address_id = null, $isPrimary = false) {
        return [
            'address_id'=>$address_id,
            'name'=>$req->input($prefix . '_name'),
            'formatted'=>$req->input($prefix . '_formatted'),
            'lat'=>$req->input($prefix . '_lat'),
            'lng'=>$req->input($prefix . '_lng'),
            'place_id'=>$req->input($prefix . '_place_id'),
            'is_primary'=>$isPrimary
        ];
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
            'address_id' => $object->address_id,
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
