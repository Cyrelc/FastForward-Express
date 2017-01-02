<?php
namespace App\Http\Repos;

use App\PhoneNumber;

class PhoneNumberRepo {
    public function GetById($id) {
        $pn = PhoneNumber::where('phone_number_id', '=', $id)->first();

        return $pn;
    }

    public function Edit($pn) {
        $old = GetByid($pn->phone_number_id);

        $old->type = $pn->type;
        $old->phone_number = $pn->phone_number;
        $old->is_primary = $pn->is_primary;

        $old->save();
    }

    public function Delete($pnId) {
        $pn = GetById($pnId);

        $pn->delete();
    }
}