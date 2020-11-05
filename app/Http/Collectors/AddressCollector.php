<?php
namespace App\Http\Collectors;

class AddressCollector {
    public function CollectMinimal($req, $prefix, $addressId = null, $isPrimary = false) {
        return [
            'address_id'=>$addressId,
            'name'=>$req->input($prefix . '_name'),
            'formatted'=>$req->input($prefix . '_formatted'),
            'lat'=>$req->input($prefix . '_lat'),
            'lng'=>$req->input($prefix . '_lng'),
            'place_id'=>$req->input($prefix . '_place_id'),
            'is_primary'=>$isPrimary
        ];
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
