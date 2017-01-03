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


    public function Edit($contact) {
        $old = GetById($contact['contact_id']);

        $old->first_name = $contact['first_name'];
        $old->last_name = $contact['last_name'];

        $old->save();
    }

    public function Delete($cid) {
        $contact = GetById($cid);

        $contact->account()->detach();
        $contact->delete();
    }
}
