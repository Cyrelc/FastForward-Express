<?php
namespace App\Http\Collectors;

class ContactsCollector {
    public function collectAll($req, $prefix, $has_address) {
        $contactCollector = new ContactCollector();
        $addressCollector = new AddressCollector();

        $new_contact_id_field = $prefix . '-new-contact-id';
        $contacts = [];
        for($i = 0; $i < $req->$new_contact_id_field; $i++) {
            $contacts[$i] = $contactCollector->Collect($req, 'contact-' . $i);
            $contacts[$i]['phone_numbers'] = $contactCollector->CollectPhoneNumbers($req, 'contact-' . $i);
            $contacts[$i]['emails'] = $contactCollector->CollectEmails($req, 'contact-' . $i);
            if($has_address)
                $contacts[$i]['address'] = $addressCollector->Collect($req, 'contact-' . $i , $has_address);
        }
        return $contacts;
    }
}
