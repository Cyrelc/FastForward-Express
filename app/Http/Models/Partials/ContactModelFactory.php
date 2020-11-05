<?php

namespace App\Http\Models\Partials;

use App\Http\Repos;
use App\Http\Models\Contact;

class ContactModelFactory {
    public function GetCreateModel() {
        $selectionsRepo = new Repos\SelectionsRepo();

        // TODO: remove action = create parameter when accounts are done (all places with old contact logic)
        $contact = new \App\Contact();
        $contact->address = new \App\Address();
        $contact->emails = collect(new \App\EmailAddress());
        $contact->emails[0] = new \App\EmailAddress();
        $contact->emails[0]->is_primary = true;
        $contact->emails[0]->action = 'create';
        $contact->phone_numbers = collect(new \App\PhoneNumber());
        $contact->phone_numbers[0] = new \App\PhoneNumber();
        $contact->phone_numbers[0]->is_primary = true;
        $contact->phone_numbers[0]->action = 'create';
        $contact->phone_types = $selectionsRepo->GetSelectionsListByType('phone_type');
        $contact->email_types = $selectionsRepo->GetSelectionsListByType('contact_type');

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
        $contact->phone_types = $selectionsRepo->GetSelectionsListByType('phone_type');
        $contact->email_types = $selectionsRepo->GetSelectionsListByType('contact_type');

        if ($getAddress)
            $contact->address = $addressRepo->GetByContactId($contact->contact_id);

        return $contact;
    }
}
