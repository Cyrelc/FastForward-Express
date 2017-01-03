<?php
namespace App\Http\Repos;

use App\EmailAddress;

class EmailAddressRepo {
    public function GetById($id) {
        $ad = EmailAddress::where('email_address_id', '=', $id)->first();

        return $ad;
    }

    public function Insert($ea) {
        $new = new EmailAddress;

        $new = $new->create($ea);

        return $new;
    }

    public function Edit($address) {
        $old = GetById($address['email_address_id'])->first();

        $old->email_address_id = $address['email_address_id'];
        $old->is_primary = $address['is_primary'];

        $old->save();
    }

    public function Delete($id) {
        $addr = GetById($id);

        $addr->delete();
    }
}
