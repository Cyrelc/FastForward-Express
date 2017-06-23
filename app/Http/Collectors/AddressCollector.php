<?php
namespace App\Http\Collectors;

class AddressCollector {
    public function Collect($req, $prefix, $isPrimary) {

        return [
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

    public function Remerge($req, $model, $prefix, $propertyName) {
        if ($req->old($prefix . "-street") !== null)
            $model[$propertyName]->street = $req->old($prefix . "-street");

        if ($req->old($prefix . "-street2") !== null)
            $model[$propertyName]->street2 = $req->old($prefix . "-street2");

        if ($req->old($prefix . "-city") !== null)
            $model[$propertyName]->city = $req->old($prefix . "-city");

        if ($req->old($prefix . "-zip-postal") !== null)
            $model[$propertyName]->zip_postal = $req->old($prefix . "-zip-postal");

        if ($req->old($prefix . "-state-province") !== null)
            $model[$propertyName]->state_province = $req->old($prefix . "-state-province");

        if ($req->old($prefix . "-country") !== null)
            $model[$propertyName]->country = $req->old($prefix . "-country");

        return $model;
    }

}
