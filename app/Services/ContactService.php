<?php

namespace App\Services;

use App\Http\Collectors;
use App\Models\Contact;
use DB;

class ContactService {
    public function create($contactData) {
        $collectedContact = $this->collect($contactData);

        DB::beginTransaction();

        $contact = Contact::create($collectedContact);

        if(isset($contactData['address']))
            $this->processAddress($contactData['address'], $contact);
        if(isset($contactData['email_addresses']))
            $this->processEmailAddresses($contactData['email_addresses'], $contact);
        if(isset($contactData['phone_numbers']))
            $this->processPhoneNumbers($contactData['phone_numbers'], $contact);

        DB::commit();

        return $contact;
    }

    public function delete($contactId) {
        DB::beginTransaction();

        $contact = Contact::find($contactId);
        if($contact->address != false)
            $contact->address->delete();
        $contact->email_addresses()->delete();
        $contact->phone_numbers()->delete();
        $contact->delete();

        DB::commit();

        return true;
    }

    public function getFull($contactId) {
        $selectionsRepo = new \App\Http\Repos\SelectionsRepo();

        $contact = Contact::with('email_addresses', 'phone_numbers', 'address')
            ->find($contactId);

        $contact->phone_types = $selectionsRepo->GetSelectionsListByType('phone_type');
        $contact->email_types = $selectionsRepo->GetSelectionsListByType('contact_type');

        return $contact;
    }

    public function update($contactData) {
        $collectedData = $this->collect($contactData);

        DB::beginTransaction();

        $contact = Contact::findOrFail($collectedData['contact_id']);
        $contact->update($collectedData);

        if(isset($contactData['address']))
            $this->processAddress($contactData['address'], $contact);
        if(isset($contactData['email_addresses']))
            $this->processEmailAddresses($contactData['email_addresses'], $contact);
        if(isset($contactData['phone_numbers']))
            $this->processPhoneNumbers($contactData['phone_numbers'], $contact);

        DB::commit();

        return $contact;
    }

    // Private Functions
    private function processAddress($addressData, $contact) {
        $addressCollector = new Collectors\AddressCollector();

        $oldAddress = false;

        $address = $addressCollector->collect($addressData, false);

        if($contact->address)
            $contact->address->update($address);
        else
            $contact->address()->create($address);

        return $contact->address;
    }

    private function processEmailAddresses($emailData, $contact) {
        foreach($emailData as $emailAddress) {
            if(isset($emailAddress['delete']))
                $contact->email_addresses->find($emailAddress['email_address_id'])->delete();
            else {
                $email = [
                    'email_address_id' => $emailAddress['email_address_id'] ?? null,
                    'email' => strtolower($emailAddress['email']),
                    'is_primary' => filter_var($emailAddress['is_primary'], FILTER_VALIDATE_BOOLEAN),
                    'type' => $emailAddress['type'] ?? null,
                ];

                if(isset($emailAddress['email_address_id']))
                    $contact->email_addresses->find($email['email_address_id'])->update($email);
                else
                    $contact->email_addresses()->create($email);
            }
        }

        return $contact->email_addresses;
    }

    private function processPhoneNumbers($phoneData, $contact) {
        foreach($phoneData as $phoneNumber) {
            if(isset($phoneNumber['phone_number_id']) && isset($phoneNumber['delete']))
                $contact->phone_numbers->find($phoneNumber['phone_number_id'])->delete();
            else {
                $phone = [
                    'phone_number_id' => isset($phoneNumber['phone_number_id']) ? $phoneNumber['phone_number_id'] : null,
                    'type' => $phoneNumber['type'],
                    'phone_number' => $phoneNumber['phone_number'],
                    'extension_number' => isset($phoneNumber['extension_number']) ? $phoneNumber['extension_number'] : null,
                    'is_primary' => filter_var($phoneNumber['is_primary'], FILTER_VALIDATE_BOOLEAN),
                ];

                if($phone['phone_number_id'])
                    $contact->phone_numbers->find($phone['phone_number_id'])->update($phone);
                else
                    $contact->phone_numbers()->create($phone);
            }
        }

        return $contact->phone_numbers;
    }

    private function collect($contactData) {
        return [
            'contact_id' => $contactData['contact_id'] ?? null,
            'first_name' => $contactData['first_name'],
            'last_name' => $contactData['last_name'],
            'position' => $contactData['position'] ?? null,
            'preferred_name' => isset($contactData['preferred_name']) ? $contactData['preferred_name'] : null,
            'pronouns' => isset($contactData['pronouns']) ? json_encode($contactData['pronouns']) : null
        ];
    }
}
