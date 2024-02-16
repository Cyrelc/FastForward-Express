<?php
namespace App\Http\Collectors;

class AddressCollector {
    public function collect($addressData, $isPrimary = null) {
        return [
            'name'=> $addressData['name'],
            'formatted'=> $addressData['formatted'],
            'is_mall'=> array_key_exists('is_mall', $addressData) ? $addressData['is_mall'] : null,
            'lat'=> $addressData['lat'],
            'lng'=> $addressData['lng'],
            'place_id'=> $addressData['place_id'],
            'is_primary'=> $isPrimary
        ];
    }

    public function collectWithPrefix($req, $prefix, $addressId = null, $isPrimary = false) {
        return [
            'address_id'=>$addressId,
            'name'=>$req->input($prefix . '_name'),
            'formatted'=>$req->input($prefix . '_formatted'),
            'is_mall'=> $req->input($prefix . '_is_mall') == null ? false : filter_var($req->input($prefix . '_is_mall'), FILTER_VALIDATE_BOOLEAN),
            'lat'=>$req->input($prefix . '_lat'),
            'lng'=>$req->input($prefix . '_lng'),
            'place_id'=>$req->input($prefix . '_place_id'),
            'is_primary'=>$isPrimary
        ];
    }

    public function toArray($object, $is_primary) {
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
