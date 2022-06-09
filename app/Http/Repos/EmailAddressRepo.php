<?php
namespace App\Http\Repos;

use App\EmailAddress;

class EmailAddressRepo {
    public function GetByContactId($cid) {
        $emails = EmailAddress::where('contact_id', '=', $cid)->get();

        return $emails;
    }

    public function GetPrimaryByContactId($contact_id) {
        $primary = EmailAddress::where('contact_id', $contact_id)
           ->where('is_primary', true);

        return $primary->first();
    }

    public function GetById($id) {
        $email = EmailAddress::where('email_address_id', '=', $id)->first();

        return $email;
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
        $old->type = $address['type'];

        $old->save();
    }

    public function Delete($id) {
        $email = $this->GetById($id);

        if (!isset($email)) return;

        $email->delete();
    }

    public function DeleteByContact($cid) {
        $emails = $this->GetByContactId($cid);

        if (!isset($emails)) return;

        foreach($emails as $email) {
            $this->delete($email->email_address_id);
        }
    }
}
