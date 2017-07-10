<?php
namespace App\Http\Collectors;


class ContactsCollector {
    public function GetDeletions($req) {
        $contactsToDelete = [];
        foreach($req->all() as $key=>$value) {
            if (substr($key, 0,15)  == "contact-delete-") {
                array_push($contactsToDelete, $req->input($key));
            }
        }

        return $contactsToDelete;
    }

    public function GetActions($req) {
        $contactActions = [];

        foreach($req->all() as $key=>$value) {
            if (substr($key, 0, 15) == "contact-action-") {
                $ids = $req->input($key);
                $type = substr($key, 15);

                if (!is_array($ids))
                    $ids = [$ids];

                foreach ($ids as $contactId) {
                    if (array_key_exists($contactId, $contactActions))
                        array_push($contactActions[$contactId], $type);
                    else
                        $contactActions[$contactId] = [$type];
                }
            }
        }

        return $contactActions;
    }

}
