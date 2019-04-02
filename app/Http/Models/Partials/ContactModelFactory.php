<?php

namespace App\Http\Models\Partials;

use App\Http\Repos;
use App\Http\Models\Contact;

class ContactModelFactory {
    public function GetCreateModel() {
        $selectionsRepo = new Repos\SelectionsRepo();

        $contact = new \App\Contact();
        $contact->address = new \App\Address();
        $contact->emails = collect(new \App\EmailAddress());
        $contact->emails[0] = new \App\EmailAddress();
        $contact->phone_numbers = collect(new \App\PhoneNumber());
        $contact->phone_numbers[0] = new \App\PhoneNumber(); 
        $contact->phone_numbers->types = $selectionsRepo->GetSelectionsByType('phone_type');
        $contact->emails->types = $selectionsRepo->GetSelectionsByType('contact_type');

        return $contact;
    }

    public function GetEditModel($contactId, $getAddress = false) {
        $contactRepo = new Repos\ContactRepo();
        $addressRepo = new Repos\AddressRepo();
        $phoneNumberRepo = new Repos\PhoneNumberRepo();
        $emailAddressRepo = new Repos\EmailAddressRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $contact = $contactRepo->GetById($contactId);

        $contact->phone_numbers = $phoneNumberRepo->GetByContactId($contact->contact_id);
        $contact->emails = $emailAddressRepo->GetByContactId($contact->contact_id);
        $contact->phone_numbers->types = $selectionsRepo->GetSelectionsByType('phone_type');
        $contact->emails->types = $selectionsRepo->GetSelectionsByType('contact_type');

        if ($getAddress)
            $contact->address = $addressRepo->GetByContactId($contact->contact_id);

        return $contact;
    }
}
