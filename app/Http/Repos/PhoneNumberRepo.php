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

    public function GetContactPrimaryPhone($contactId) {
        $primaryPhone = PhoneNumber::where([['contact_id', '=', $contactId], ['is_primary', '=', true]])->first();

        return $primaryPhone;
    }

    public function GetPagerByDriverContact($id) {
        $pn = PhoneNumber::where('contact_id', '=', $id)
            ->where('type', '=', 'Pager')->first();

        return $pn;
    }

    public function Handle($phone, $contact_id) {
        if($phone['action'] == 'delete' && $phone['db-id'] != '')
            $this->Delete($phone['phone_number_id']);
        else if ($phone['action'] == 'create') {
            $phone['contact_id'] = $contact_id;
            $this->Insert($phone);
        } else if ($phone['action'] == 'update') {
            $phone['contact_id'] = $contact_id;
            $this->Update($phone);
        }
    }

    public function Insert($pn) {
        $new = new PhoneNumber;

        $new = $new->create($pn);
        
        return $new;
    }

    public function Update($phone) {
        $old = $this->GetByid($phone['phone_number_id']);

        //We don't really deal with pn type right now
        $old->type = $phone['type'];
        $old->phone_number = $phone['phone_number'];
        $old->extension_number = $phone['extension_number'];
        // Temporarily depreciating concept of "primary" and "secondary" phone numbers
        // $old->is_primary = $phone['is_primary'];
        $old->contact_id = $phone['contact_id'];

        $old->save();
    }

    public function Delete($pnId) {
        $pn = $this->GetById($pnId);

        if (!isset($pn)) return;

        $pn->delete();
    }

    public function DeleteByContact($cid) {
        $pns = $this->ListByContactId($cid);

        foreach($pns as $pn) {
            $this->delete($pn->phone_number_id);
        }
    }
}
