<?php
namespace App\Http\Repos;

use App\AccountUser;
use App\Contact;
use App\EmployeeEmergencyContact;

use Illuminate\Support\Facades\DB;

class ContactRepo {
    public function GetById($contactId) {
        $contact = Contact::where('contact_id', $contactId)->first();

        return $contact;
    }

    public function Insert($contact) {
        $new = new Contact;

        $new = $new->create($contact);

        return $new;
    }

    public function Update($contact) {
        $old = $this->GetById($contact['contact_id']);

        $old->first_name = $contact['first_name'];
        $old->last_name = $contact['last_name'];
        $old->position = $contact['position'];

        $old->save();

        return $old;
    }

    public function Delete($contactId) {
        $contact = $this->GetById($contactId);

        $phoneRepo = new PhoneNumberRepo();
        $addressRepo = new AddressRepo();
        $emailRepo = new EmailAddressRepo();

        $phoneRepo->DeleteByContact($contactId);
        $addressRepo->DeleteByContact($contactId);
        $emailRepo->DeleteByContact($contactId);

        $contact->delete();
    }

    public function DeleteEmployeeEmergencyContact($employeeId, $contactId) {
        $phoneRepo = new PhoneNumberRepo();
        $emailRepo = new EmailAddressRepo();
        $addressRepo = new AddressRepo();

        $contact = $this->GetById($contactId);
        $employeeEmergencyContact = EmployeeEmergencyContact::where('employee_id', $employeeId)
            ->where('contact_id', $contactId)->first();
        if($employeeEmergencyContact->is_primary)
            throw new \Exception('Unable to delete primary emergency contact. Please set another contact to primary, save, and try again.');
        $employeeEmergencyContact->delete();

        $addressRepo->DeleteByContact($contactId);
        $emailRepo->DeleteByContact($contactId);
        $phoneRepo->DeleteByContact($contactId);

        $contact->delete();
    }

    // public function SetEmployeePrimaryEmergencyContact($employeeId, $contactId) {
    //     $currentPrimary = EmployeeEmergencyContact::where('employee_id', $employeeId)
    //         ->where('is_primary', 1)
    //         ->first();

    //     $currentPrimary->is_primary = 0;
    //     $currentPrimary->save();

    //     $newPrimary = EmployeeEmergencyContact::where('employee_id', $employeeId)
    //         ->where('contact_id', $contactId)
    //         ->first();
    //     $newPrimary->is_primary = 1;
    //     $newPrimary->save();
    // }
}
