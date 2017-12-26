<?php

namespace App\Http\Models\Partials;

use App\Http\Repos;
use App\Http\Models\Contact;

class ContactModelFactory {
    public function GetCreateModel() {
        $model = new \App\Contact();
        $model->address = new \App\Address();

        return $model;
    }

    public function GetEditModel($contactId, $getAddress) {
        $contactRepo = new Repos\ContactRepo();
        $addressRepo = new Repos\AddressRepo();
        $phoneNumberRepo = new Repos\PhoneNumberRepo();
        $emailAddressRepo = new Repos\EmailAddressRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $contact = $contactRepo->GetById($contactId);

        $contact->phone_numbers = $phoneNumberRepo->ListByContactId($contact->contact_id);
        $contact->phone_numbers->types = $selectionsRepo->GetSelectionsByType('phone_type');

        $emails = $emailAddressRepo->ListByContactId($contact->contact_id);
        foreach($emails as $email){
            if ($email["is_primary"])
                $contact->primaryEmail = $email;
            else
                $contact->secondaryEmail = $email;
        }

        if ($getAddress)
            $contact->address = $addressRepo->GetByContactId($contact->contact_id);

        return $contact;
    }
}
