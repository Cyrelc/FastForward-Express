<?php
namespace App\Http\Repos;

use App\EmailAddress;

class EmailAddressRepo {
    public function ListByContactId($cid) {
        $ems = EmailAddress::where('contact_id', '=', $cid)->get();

        return $ems;
    }

    public function GetById($id) {
        $ad = EmailAddress::where('email_address_id', '=', $id)->first();

        return $ad;
    }

    public function Insert($ea) {
        $new = new EmailAddress;

        $new = $new->create($ea);

        return $new;
    }

    public function Update($address) {
        $old = $this->GetById($address['email_address_id']);

        $old->email = $address['email'];
        $old->is_primary = $address['is_primary'];
        $old->contact_id = $address['contact_id'];

        $old->save();
    }

    public function Delete($id) {
        $addr = $this->GetById($id);

        if (!isset($addr)) return;

        $addr->delete();
    }

    public function DeleteByContact($cid) {
        $eAddrs = $this->ListByContactId($cid);

        if (!isset($eAddrs)) return;

        foreach($eAddrs as $eAddr) {
            $this->delete($eAddr->email_address_id);
        }
    }
}
