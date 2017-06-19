<?php
namespace App\Http\Repos;

use App\PhoneNumber;

class PhoneNumberRepo {
    public function ListByContactId($cid) {
        $pns = PhoneNumber::where('contact_id', '=', $cid)->get();

        return $pns;
    }

    public function GetById($id) {
        $pn = PhoneNumber::where('phone_number_id', '=', $id)->first();

        return $pn;
    }

    public function Insert($pn) {
        $new = new PhoneNumber;

        $new = $new->create($pn);

        return $new;
    }

    public function Update($pn) {
        $old = $this->GetByid($pn['phone_number_id']);

        //We don't really deal with pn type right now
        //$old->type = $pn['type'];
        $old->phone_number = $pn['phone_number'];
        $old->is_primary = $pn['is_primary'];
        $old->contact_id = $pn['contact_id'];

        $old->save();
    }

    public function Delete($pnId) {
        $pn = $this->GetById($pnId);

        $pn->delete();
    }

    public function DeleteByContact($cid) {
        $pns = $this->ListByContactId($cid);

        foreach($pns as $pn) {
            $this->delete($pn);
        }
    }
}
