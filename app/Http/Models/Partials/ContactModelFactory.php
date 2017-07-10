<?php

namespace App\Http\Models\Partials;

use App\Http\Repos;
use App\Http\Models\Contact;

class ContactModelFactory {
    public function GetEditModel($contactId, $getAddress) {
        $contactRepo = new Repos\ContactRepo();
        $addressRepo = new Repos\AddressRepo();
        $phoneNumberRepo = new Repos\PhoneNumberRepo();
        $emailAddressRepo = new Repos\EmailAddressRepo();

        $contact = $contactRepo->GetById($contactId);

        $phoneNumbers = $phoneNumberRepo->ListByContactId($contact->contact_id);
        foreach($phoneNumbers as $phoneNumber){
            if ($phoneNumber->type === "pager") continue;

            if ($phoneNumber["is_primary"])
                $contact->primaryPhone = $phoneNumber;
            else
                $contact->secondaryPhone = $phoneNumber;
        }

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
