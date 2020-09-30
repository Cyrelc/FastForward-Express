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
                    'type' => isset($email['type']) ? $email['type'] : null,
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

    public function ProcessPhonesForContact($req, $contact_id) {
        $phoneRepo = new \App\Http\Repos\PhoneNumberRepo();

        foreach($req->phone as $i => $phone) {
            $is_primary = $req->phone_is_primary[0] == $i;
            $phone = [
                'phone_number_id' => $req->phone_number_id[$i],
                'type' => $req->phone_type[$i],
                'phone_number' => $req->phone[$i],
                'extension_number' => $req->extension[$i],
                'is_primary' => $is_primary,
                'contact_id' => $contact_id
            ];
            if($req->phone_action[$i] == 'create')
                $phoneRepo->Insert($phone);
            else if($req->phone_action[$i] == 'delete' && $phone['phone_number_id'] != '')
                $phoneRepo->Delete($phone['phone_number_id']);
            else if($req->phone_action[$i] == 'update')
                $phoneRepo->Update($phone);
        }
    }

    public function ProcessEmailsForContact($req, $contact_id) {
        $emailRepo = new \App\Http\Repos\EmailAddressRepo();

        foreach($req->email as $i => $email) {
            $is_primary = $req->email_is_primary[0] == $i;
            $email = [
                'email_address_id' => $req->email_address_id[$i],
                'email' => $req->email[$i],
                'type' => $req->email_type[$i] ? $req->email_type[$i] : null,
                'is_primary' => $is_primary,
                'contact_id' => $contact_id
            ];
            if($req->email_action[$i] == 'create')
                $emailRepo->Insert($email);
            else if($req->email_action[$i] == 'delete' && $email['email_address_id'] != '')
                $emailRepo->Delete($email['email_address_id']);
            else if($req->email_action[$i] == 'update')
                $emailRepo->Update($email); 
        }
    }

    public function ToObject($contactArray, $phoneNumberArray, $emailArray, $secondaryPhoneNumberArray, $secondaryEmailArray){
        $contact = new \App\Contact();
        $phoneNumber = new \App\PhoneNumber();
        $emailAddress = new \App\EmailAddress();
        $phoneNumber2 = new \App\PhoneNumber();
        $emailAddress2 = new \App\EmailAddress();

        $contact->first_name = $contactArray['first_name'];
        $contact->last_name = $contactArray['last_name'];
        $contact->position = $contactArray['position'];

        $phoneNumber->phone_number_id = in_array('phone_number_id', $phoneNumberArray) ? $phoneNumberArray['phone_number_id'] : -2;
        $phoneNumber->phone_number = $phoneNumberArray['phone_number'];
        $phoneNumber->extension_number = $phoneNumberArray['extension_number'];
        $emailAddress->email_address_id = in_array('email_address_id', $emailArray) ? $emailArray['email_address_id'] : -2;
        $emailAddress->email = $emailArray['email'];

        $phoneNumber2->phone_number_id = in_array('phone_number_id', $secondaryPhoneNumberArray) ? $secondaryPhoneNumberArray['phone_number_id'] : -2;
        $phoneNumber2->phone_number = $secondaryPhoneNumberArray['phone_number'];
        $phoneNumber2->extension_number = $secondaryPhoneNumberArray['extension_number'];

        $emailAddress2->email_address_id = in_array('email_address_id', $secondaryEmailArray) ? $secondaryEmailArray['email_address_id'] : -2;
        $emailAddress2->email = $secondaryEmailArray['email'];

        $contact->primaryPhone = $phoneNumber;
        $contact->primaryEmail = $emailAddress;
        $contact->secondaryPhone = $phoneNumber2;
        $contact->secondaryEmail = $emailAddress2;

        return $contact;
    }
}
