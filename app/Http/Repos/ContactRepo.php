<?php
namespace App\Http\Repos;

use App\Contact;

class ContactRepo {

    public function ListAll() {
        $contacts = Contact::All();

        return $contacts;
    }

    public function GetById($id) {
        $contact = Contact::where('contact_id', '=', $id)->first();

        return $contact;
    }

    public function Insert($contact) {
        $new = new Contact;

        $new = $new->create($contact);

        return $new;
    }


    public function Update($contact) {
        $old = $this->GetById($contact["contact_id"]);

        $old->first_name = $contact["first_name"];
        $old->last_name = $contact["last_name"];
        $old->position = $contact["position"];

        $old->save();

        return $old;
    }

    public function Delete($cid) {
        $contact = $this->GetById($cid);

        $pnRepo = new PhoneNumberRepo();
        $addrRepo = new AddressRepo();
        $emailRepo = new EmailAddressRepo();

        $contact->accounts()->detach();
        $contact->employees()->detach();
        $pnRepo->DeleteByContact($cid);
        $addrRepo->DeleteByContact($cid);
        $emailRepo->DeleteByContact($cid);

        $contact->delete();
    }
}
