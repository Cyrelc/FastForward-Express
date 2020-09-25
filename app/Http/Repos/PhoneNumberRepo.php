<?php
namespace App\Http\Repos;

use App\PhoneNumber;

class PhoneNumberRepo {
    public function GetByContactId($cid) {
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
        if($phone['action'] == 'delete' && $phone['phone_number_id'] != '')
            $this->Delete($phone['phone_number_id']);
        else if ($phone['action'] == 'create') {
            $phone['contact_id'] = $contact_id;
            $this->Insert($phone);
        } else if ($phone['action'] == 'update') {
            $phone['contact_id'] = $contact_id;
            $this->Update($phone);
        }
    }

    public function Insert($phone) {
        $new = new PhoneNumber;

        $new = $new->create($phone);
        
        return $new;
    }

    public function Update($phone) {
        $old = $this->GetById($phone['phone_number_id']);

        $old->type = $phone['type'];
        $old->phone_number = $phone['phone_number'];
        $old->extension_number = $phone['extension_number'];
        $old->is_primary = $phone['is_primary'];

        $old->save();
    }

    public function Delete($pnId) {
        $pn = $this->GetById($pnId);

        if (!isset($pn)) return;

        $pn->delete();
    }

    public function DeleteByContact($cid) {
        $pns = $this->GetByContactId($cid);

        foreach($pns as $pn) {
            $this->delete($pn->phone_number_id);
        }
    }
}
