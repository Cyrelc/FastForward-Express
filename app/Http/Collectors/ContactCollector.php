<?php

namespace App\Http\Collectors;

use \App\Http\Validation\Utils;

class ContactCollector {
    public function GetContact($req, $contactId = null) {
        return [
            'first_name' => $req->first_name,
            'last_name' => $req->last_name,
            'position' => $req->position,
            'contact_id' => $contactId
        ];
    }

    public function ProcessAddressForContact($req, $contactId) {
        $addressRepo = new \App\Http\Repos\AddressRepo();
        $oldAddress = $addressRepo->GetByContactId($contactId);
        $address = [
            'address_id' => $oldAddress ? $oldAddress->address_id : null,
            'contact_id' => $contactId,
            'formatted' => $req->address_formatted,
            'lat' => $req->address_lat,
            'lng' => $req->address_lng,
            'name' => $req->address_name,
            'place_id' => $req->address_place_id,
        ];
        if($oldAddress)
            $addressRepo->UpdateMinimal($address);
        else
            $addressRepo->InsertMinimal($address);
    }

    public function ProcessEmailAddressesForContact($req, $contactId) {
        $emailRepo = new \App\Http\Repos\EmailAddressRepo();
        foreach($req->emails as $email) {
            if(isset($email['delete']))
                $emailRepo->Delete($email['email_address_id']);
            else {
                $email = [
                    'email_address_id' => isset($email['email_address_id']) ? $email['email_address_id'] : null,
                    'email' => strtolower($email['email']),
                    'type' => isset($email['type']) && $email['type'] != "" ? json_encode($email['type']) : null,
                    'is_primary' => filter_var($email['is_primary'], FILTER_VALIDATE_BOOLEAN),
                    'contact_id' => $contactId
                ];
                if($email['email_address_id'])
                    $emailRepo->Update($email);
                else
                    $emailRepo->Insert($email);
            }
        }
    }

    public function ProcessPhoneNumbersForContact($req, $contactId) {
        $phoneRepo = new \App\Http\Repos\PhoneNumberRepo();
        foreach($req->phone_numbers as $phoneNumber) {
            if(isset($phoneNumber['delete']))
                $phoneRepo->Delete($phone['phone_number_id']);
            else {
                $phone = [
                    'phone_number_id' => isset($phoneNumber['phone_number_id']) ? $phoneNumber['phone_number_id'] : null,
                    'type' => $phoneNumber['type'],
                    'phone_number' => $phoneNumber['phone_number'],
                    'extension_number' => isset($phoneNumber['extension_number']) ? $phoneNumber['extension_number'] : null,
                    'is_primary' => filter_var($phoneNumber['is_primary'], FILTER_VALIDATE_BOOLEAN),
                    'contact_id' => $contactId
                ];
                if($phone['phone_number_id'])
                    $phoneRepo->Update($phone);
                else
                    $phoneRepo->Insert($phone);
            }
        }
    }
}
