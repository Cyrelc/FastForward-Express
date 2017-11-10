<?php

namespace App\Http\Collectors;

use \App\Http\Validation\Utils;

class ContactCollector {
    public function Collect($req, $prefix){
        return [
            'first_name'=>$req->input($prefix . '-first-name'),
            'last_name'=>$req->input($prefix . '-last-name'),
            'position'=>$req->input($prefix . '-position')
        ];
    }

    public function CollectPhoneNumber($req, $contactId, $isPrimary, $newId = null) {
        $prefix = 'contact-' . $contactId . ($isPrimary ? '-phone1' : '-phone2');
        return $this->CollectPhoneNumberSingle($req, $prefix, $contactId, $isPrimary, $newId);
    }

    public function CollectPhoneNumberSingle($req, $prefix, $contactId, $isPrimary, $newId = null) {
        return [
            'phone_number_id' => $req->input($prefix . '-id'),
            'phone_number' => $req->input($prefix),
            'extension_number' => $req->input($prefix . '-ext'),
            'is_primary' => $isPrimary,
            'contact_id' => isset($newId) ? $newId : $contactId
        ];
    }

    public function CollectEmail($req, $contactId, $isPrimary, $newId = null) {
        $prefix = 'contact-' . $contactId . ($isPrimary ? '-email1' : '-email2');

        return $this->CollectEmailSingle($req, $prefix, $contactId, $isPrimary, $newId);
    }

    public function CollectEmailSingle($req, $prefix, $contactId, $isPrimary, $newId = null) {
        return [
            'email_address_id'=>$req->input($prefix . '-id'),
            'email'=>$req->input($prefix),
            'contact_id'=>isset($newId) ? $newId : $contactId,
            'is_primary'=>$isPrimary
        ];
    }

    public function Remerge($req, $contacts, $includeAddress) {
        if (!is_array($contacts))
            $contacts = [];

        $newContacts = [];
        $deleteContacts = [];

        $primary = $req->old('contact-action-change-primary') === null ? -1 : $req->old('contact-action-change-primary');

        if ($req->old('contact-action-new') !== null) {
            $newContacts = $req->old('contact-action-new');

            if (!is_array($newContacts))
                $newContacts = [$newContacts];
        }

        if ($req->old('contact-action-delete') !== null) {
            $deleteContacts = $req->old('contact-action-delete');

            if (!is_array($deleteContacts))
                $deleteContacts = [$deleteContacts];
        }

        $phoneNumbersToDelete = $req->old('pn-action-delete');
        $emailsToDelete = $req->old('em-action-delete');

        if ($phoneNumbersToDelete === null)
            $phoneNumbersToDelete = [];

        if ($emailsToDelete === null)
            $emailsToDelete = [];

        for($i = 0; $i < count($contacts); $i++) {
            $contactId = $contacts[$i]->contact_id;

            if (in_array($contactId, $newContacts))
                $contacts[$i]->is_new = 'true';
            else
                $contacts[$i]->is_new = 'false';

            if (in_array($contactId, $deleteContacts))
                $contacts[$i]->delete = 'true';
            else
                $contacts[$i]->delete = 'false';

            if ($primary !== -1) {
                if ($contactId == $primary)
                    $contacts[$i]->is_primary = 'true';
                else
                    $contacts[$i]->is_primary = 'false';
            }

            $contacts[$i] = $this->RemergeContact($req, $contacts[$i], $contactId,'contact-' . $contactId, $includeAddress);

            if ($req->old('pn-action-add-' . $contactId) !== null || $req->input('contact-' . $contactId . '-phone2-id') !== null) {
                $contacts[$i]->secondaryPhone = new \App\PhoneNumber();

                if ($req->old('pn-action-add-' . $contactId) !== null) {
                    $contacts[$i]->secondaryPhone->is_new = true;
                }

                $contacts[$i]->secondaryPhone->phone_number_id = $req->old('contact-' . $contactId . '-phone2-id');

                if ($contacts[$i]->secondaryPhone->phone_number_id !== null && in_array($contacts[$i]->secondaryPhone->phone_number_id, $phoneNumbersToDelete))
                    $contacts[$i]->secondaryPhone->delete = true;
                else
                    $contacts[$i]->secondaryPhone->delete = false;

                if ($req->old('contact-' . $contactId . '-phone2') !== null)
                    $contacts[$i]->secondaryPhone->phone_number = $req->old('contact-' . $contactId . '-phone2');

                $contacts[$i]->secondaryPhone->extension_number = $req->old('contact-' . $contactId . '-phone2-ext');
            }

            if ($req->old('em-action-add-' . $contactId) !== null || $req->old('contact-' . $contactId . '-email2-id') !== null) {
                $contacts[$i]->secondaryEmail = new \App\EmailAddress();

                if ($req->old('em-action-add-' . $contactId) !== null)
                    $contacts[$i]->secondaryEmail->is_new = true;

                $contacts[$i]->secondaryEmail->email_address_id = $req->old('contact-' . $contactId . '-email2-id');

                if ($contacts[$i]->secondaryEmail->email_address_id !== null && in_array($contacts[$i]->secondaryEmail->email_address_id, $emailsToDelete))
                    $contacts[$i]->secondaryEmail->delete = true;
                else
                    $contacts[$i]->secondaryEmail->delete = false;

                if ($req->old('contact-' . $contactId . '-email2') !== null)
                    $contacts[$i]->secondaryEmail->email = $req->old('contact-' . $contactId . '-email2');
            }

            if ($includeAddress) {
                $addressCollector = new \App\Http\Collectors\AddressCollector();
                $contacts[$i]->address = $addressCollector->Remerge($req, $contacts[$i]->address, 'contact-' . $contactId . '-address');
            }
        }

        for($i = 0; $i < count($newContacts); $i++) {
            $contact = new \App\Contact();
            $contact->is_new = 'true';
            //dd($contacts);

            array_push($contacts, $this->RemergeContact($req, $contact, $newContacts[$i], 'contact-' . $newContacts[$i], $includeAddress));
        }

        return $contacts;
    }

    public function RemergeContact($req, $contact, $id, $prefix, $includeAddress) {
        $firstName = $req->old($prefix . '-first-name');
        $lastName = $req->old($prefix . '-last-name');
        $position = $req->old($prefix . '-position');
        $phone1 = $req->old($prefix . '-phone1');
        $phone1Ext = $req->old($prefix . '-phone1-ext');
        $email1 = $req->old($prefix . '-email1');
        $phone2 = $req->old($prefix . '-phone2');
        $phone2Ext = $req->old($prefix . '-phone2-ext');
        $email2 = $req->old($prefix . '-email2');

        if(!isset($contact->primaryPhone))
            $contact->primaryPhone = new \App\PhoneNumber();

        if(!isset($contact->secondaryPhone))
            $contact->secondaryPhone = new \App\PhoneNumber();

        if(!isset($contact->primaryEmail))
            $contact->primaryEmail = new \App\EmailAddress();

        if(!isset($contact->secondaryEmail))
            $contact->secondaryEmail = new \App\EmailAddress();

        if (!Utils::HasValue($contact->contact_id))
            $contact->contact_id = $id;

        if (Utils::HasValue($firstName))
            $contact->first_name = $firstName;

        if (Utils::HasValue($lastName))
            $contact->last_name = $lastName;

        if (Utils::HasValue($position))
            $contact->position = $position;

        if (Utils::HasValue($phone1))
            $contact->primaryPhone->phone_number = $phone1;

        if (Utils::HasValue($phone1Ext))
            $contact->primaryPhone->extension_number = $phone1Ext;

        if (Utils::HasValue($email1))
            $contact->primaryEmail->email = $email1;

        if (Utils::HasValue($phone2))
            $contact->secondaryPhone->phone_number = $phone2;

        if (Utils::HasValue($phone2Ext))
            $contact->secondaryPhone->extension_number = $phone2Ext;

        if (Utils::HasValue($email2))
            $contact->secondaryEmail->email = $email2;

        if ($includeAddress) {
            if (!isset($contact->address))
                $contact->address = new \App\Address();

            $street = $req->old($prefix . '-address-street');
            $street2 = $req->old($prefix . '-address-street2');
            $city = $req->old($prefix . '-address-city');
            $state = $req->old($prefix . '-address-state-province');
            $zip = $req->old($prefix . '-address-zip-postal');
            $country = $req->old($prefix . '-address-country');

            if (Utils::HasValue($street))
                $contact->address->street = $street;

            if (Utils::HasValue($street2))
                $contact->address->street2 = $street2;

            if (Utils::HasValue($city))
                $contact->address->city = $city;

            if (Utils::HasValue($state))
                $contact->address->state_province = $state;

            if (Utils::HasValue($zip))
                $contact->address->zip_postal = $zip;

            if (Utils::HasValue($country))
                $contact->address->country = $country;
        }
        //dd($contact);
        return $contact;
    }

    public function RemergePhoneNumberSingle($req, $phone, $name) {
        $phone_number = $req->old($name);
        $extension = $req->old($name . '-ext');

        if (Utils::HasValue($phone_number))
            $phone->phone_number = $phone_number;

        if (Utils::HasValue($extension))
            $phone->extension_number = $extension;

        return $phone;
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
