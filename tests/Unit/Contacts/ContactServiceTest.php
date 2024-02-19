<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

use App\Models\Address;
use App\Models\Contact;
use App\Models\EmailAddress;
use App\Models\PhoneNumber;
use App\Services\ContactService;

class ContactServiceTest extends TestCase {
    use RefreshDatabase;

    public function testCreateContact() {
        //arrange
        $contactService = new ContactService;

        $contact = Contact::factory()->make();
        $contactData = $contact->toArray();

        //act
        $result = $contactService->create($contactData);
        //assert
        $this->assertEquals($result->first_name, $contact->first_name);
        $this->assertEquals($result->last_name, $contact->last_name);
        $this->assertDatabaseHas('contacts', $contact->toArray());
    }

    public function testCreateContactWithAddress() {
        //arrange
        $contactService = new ContactService;

        $address = Address::factory()->make();
        $contact = Contact::factory()->make();
        $contactData = $contact->toArray();

        $contactData['address'] = $address->toArray();

        //act
        $result = $contactService->create($contactData);
        //assert
        $this->assertEquals($result->first_name, $contact->first_name);
        $this->assertEquals($result->last_name, $contact->last_name);
        $this->assertDatabaseHas('addresses', ['contact_id' => $result->contact_id, 'formatted' => $address->formatted]);
    }

    public function testCreateContactWithEmailAddresses() {
        //arrange
        $contactService = new ContactService;

        $contact = Contact::factory()->make();
        $emails = EmailAddress::factory()->count(3)->make(['contact_id' => null]);
        $emails[1]->is_primary = true;
        $contactData = $contact->toArray();
        $contactData['email_addresses'] = $emails->toArray();
        //act
        $result = $contactService->create($contactData);
        //assert
        $this->assertEquals($result->first_name, $contact->first_name);
        $this->assertEquals($result->last_name, $contact->last_name);
        foreach($emails as $email) {
            $email->contact_id = $result->contact_id;
            $this->assertEmailAddressExists($email);
        }
    }

    public function testCreateContactWithPhoneNumbers() {
        //arrange
        $contactService = new ContactService;

        $contact = Contact::factory()->make();
        $phone1 = PhoneNumber::factory()->make(['contact_id' => null, 'is_primary' => true]);
        $phone2 = PhoneNumber::factory()->make(['contact_id' => null]);
        $contactData = $contact->toArray();
        $contactData['phone_numbers'] = [
            $phone1->toArray(),
            $phone2->toArray()
        ];

        //act
        $result = $contactService->create($contactData);
        //assert
        $this->assertEquals($result->first_name, $contact->first_name);
        $this->assertEquals($result->last_name, $contact->last_name);
        $this->assertDatabaseHas('phone_numbers', array_merge($phone1->toArray(), ['contact_id' => $result->contact_id]));
        $this->assertDatabaseHas('phone_numbers', array_merge($phone2->toArray(), ['contact_id' => $result->contact_id]));
    }

    public function testCreateContactWithAll() {
        //arrange
        $contactService = new ContactService;

        $address = Address::factory()->make();
        $contact = Contact::factory()->make();
        $emailAddresses = EmailAddress::factory()->count(2)->make(['contact_id' => null]);
        $emailAddresses[0]->is_primary = true;
        $phoneNumbers = PhoneNumber::factory()->count(2)->make(['contact_id' => null]);
        $phoneNumbers[1]->is_primary = true;

        $contactData = $contact->toArray();
        $contactData['address'] = $address->toArray();
        $contactData['phone_numbers'] = $phoneNumbers->toArray();
        $contactData['email_addresses'] = $emailAddresses->toArray();

        //act
        $result = $contactService->create($contactData);
        //assert
        $this->assertEquals($result->first_name, $contact->first_name);
        $this->assertEquals($result->last_name, $contact->last_name);
        $this->assertDatabaseHas('contacts', $contact->toArray());
        foreach($phoneNumbers as $phoneNumber) {
            $this->assertDatabaseHas('phone_numbers', array_merge($phoneNumber->toArray(), [
                'contact_id' => $result->contact_id,
            ]));
        }
        foreach($emailAddresses as $emailAddress) {
            $this->assertDatabaseHas('email_addresses', array_merge($emailAddress->toArray(), [
                'contact_id' => $result->contact_id,
                'type' => $this->castAsJson($emailAddress->type)
            ]));
        }
        $this->assertDatabaseHas('addresses', ['contact_id' => $result->contact_id, 'formatted' => $address->formatted]);
    }

    public function testUpdateContact() {
        //arrange
        $contactService = new ContactService();
        $contact = Contact::factory()->create();
        $newContact = Contact::factory()->make();
        $contactData = $newContact->toArray();
        $contactData['contact_id'] = $contact->contact_id;
        //act
        $result = $contactService->update($contactData);
        //assert
        $this->assertDatabaseCount('contacts', 1);
        $this->assertEquals($result->contact_id, $contact->contact_id);
        $this->assertDatabaseHas('contacts', $contactData);
    }

    public function testUpdateContactAndAddress() {
        $contactService = new ContactService();
        $contact = Contact::factory()->create();
        $address = Address::factory()->create(['contact_id' => $contact->contact_id]);
        $newContact = Contact::factory()->make(['contact_id' => $contact->contact_id]);
        $newAddress = Address::factory()->make(['address_id' => $address->address_id, 'contact_id' => $contact->contact_id]);

        $newContactData = $newContact->toArray();
        $newContactData['address'] = $newAddress->toArray();

        $result = $contactService->update($newContactData);

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseHas('addresses', $newAddress->toArray());
        $this->assertDatabaseHas('contacts', $newContact->toArray());
    }

    public function testUpdateContactAndEmailAddresses() {
        $contactService = new ContactService();
        $contact = Contact::factory()->create();
        $emailAddress = EmailAddress::factory()->create(['contact_id' => $contact->contact_id]);
        $emailAddress2 = EmailAddress::factory()->create(['contact_id' => $contact->contact_id]);
        $newContact = Contact::factory()->make(['contact_id' => $contact->contact_id]);
        $newEmail1 = EmailAddress::factory()->make([
            'contact_id' => $contact->contact_id,
            'email_address_id' => $emailAddress->email_address_id,
        ]);
        $newEmail2 = EmailAddress::factory()->make([
            'contact_id' => $contact->contact_id,
            'email_address_id' => $emailAddress2->email_address_id,
        ]);

        $newContactData = $newContact->toArray();
        $newContactData['email_addresses'] = [
            $newEmail1->toArray(),
            $newEmail2->toArray()
        ];

        $result = $contactService->update($newContactData);

        $this->assertDatabaseCount('email_addresses', 2);
        $this->assertDatabaseCount('contacts', 1);
        $this->assertEmailAddressExists($newEmail1);
        $this->assertEmailAddressExists($newEmail2);
        $this->assertDatabaseHas('contacts', $newContact->toArray());
    }

    public function testUpdateContactAndPhoneNumbers() {
        $contactService = new ContactService;

        $contact = Contact::factory()->create();
        $phone1 = PhoneNumber::factory()->create(['contact_id' => $contact->contact_id]);
        $phone2 = PhoneNumber::factory()->create(['contact_id' => $contact->contact_id]);
        $phone3 = PhoneNumber::factory()->create(['contact_id' => $contact->contact_id]);
        $updatedPhone1 = PhoneNumber::factory()->make(['contact_id' => $contact->contact_id, 'phone_number_id' => $phone1->phone_number_id]);
        $updatedPhone3 = PhoneNumber::factory()->make(['contact_id' => $contact->contact_id, 'phone_number_id' => $phone3->phone_number_id]);

        $newContactData = $contact->toArray();
        $newContactData['phone_numbers'] = [
            $updatedPhone1->toArray(),
            $updatedPhone3->toArray()
        ];

        $result = $contactService->update($newContactData);

        $this->assertDatabaseCount('phone_numbers', 3);
        $this->assertDatabaseHas('phone_numbers', $updatedPhone1->toArray());
        $this->assertDatabaseHas('phone_numbers', $phone2->toArray());
        $this->assertDatabaseHas('phone_numbers', $updatedPhone3->toArray());
    }

    public function testDeleteEmail() {
        $contactService = new ContactService();
        $contact = Contact::factory()->create();
        $emailAddress1 = EmailAddress::factory()->create(['contact_id' => $contact->contact_id]);
        $emailAddress2 = EmailAddress::factory()->create(['contact_id' => $contact->contact_id]);
        $emailAddress3 = EmailAddress::factory()->create(['contact_id' => $contact->contact_id, 'is_primary' => true]);

        $newContactData = $contact->toArray();
        $newContactData['email_addresses'] = [
            array_merge($emailAddress1->toArray(), ['delete' => true])
        ];

        $result = $contactService->update($newContactData);

        $this->assertDatabaseCount('email_addresses', 2);
        $this->assertDatabaseCount('contacts', 1);
        $this->assertEmailAddressExists($emailAddress2);
        $this->assertEmailAddressExists($emailAddress3);
        $this->assertDatabaseMissing('email_addresses', $emailAddress1->toArray());
        $this->assertDatabaseHas('contacts', $contact->toArray());
    }

    public function testDeletePhone() {
        $contactService = new ContactService;

        $contact = Contact::factory()->create();
        $phone1 = PhoneNumber::factory()->create(['contact_id' => $contact->contact_id]);
        $phone2 = PhoneNumber::factory()->create(['contact_id' => $contact->contact_id]);
        $phone3 = PhoneNumber::factory()->create(['contact_id' => $contact->contact_id]);

        $newContactData = $contact->toArray();
        $newContactData['phone_numbers'] = [
            array_merge($phone2->toArray(), ['delete' => true])
        ];

        $result = $contactService->update($newContactData);

        $this->assertDatabaseCount('phone_numbers', 2);
        $this->assertDatabaseHas('phone_numbers', $phone1->toArray());
        $this->assertDatabaseMissing('phone_numbers', $phone2->toArray());
        $this->assertDatabaseHas('phone_numbers', $phone3->toArray());
        $this->assertDatabaseHas('contacts', $contact->toArray());
    }
}
