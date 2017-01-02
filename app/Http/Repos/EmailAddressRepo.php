<?php
namespace App\Http\Repos;

use App\EmailAddress;

class EmailAddressRepo {
    public function GetById($id) {
        $ad = EmailAddress::where('email_address_id', '=', $id)->first();

        return $ad;
    }

    public function Edit($addr) {
        $old = Address::where('email_address_id', '=', $addr->email_address_id)->first();

        $old->email_address_id = $addr->email_address_id;
        $old->is_primary = $addr->is_primary;

        $old->save();
    }

    public function Delete($id) {
        $addr = GetById($id);

        $addr->delete();
    }
}