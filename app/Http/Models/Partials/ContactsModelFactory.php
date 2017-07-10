<?php
namespace App\Http\Models\Partials;

use App\Http\Repos;

class ContactsModelFactory {
    public function GetEditModel($joinTableContacts, $getAddress) {
        $primary = -1;
        $contacts = [];
        $contactFactory = new ContactModelFactory();

        //Find primary contact
        foreach($joinTableContacts as $contact)
        {
            if ($contact->is_primary == 1)
                $primary = $contact->contact_id;
        }

        for($i = 0; $i < count($joinTableContacts); $i++) {
            array_push($contacts, $contactFactory->GetEditModel($joinTableContacts[$i]->contact_id, $getAddress));

            if (($contacts[$i])->contact_id == $primary)
                ($contacts[$i])->is_primary = true;
            else
                ($contacts[$i])->is_primary = false;
        }

        return $contacts;
    }
}
