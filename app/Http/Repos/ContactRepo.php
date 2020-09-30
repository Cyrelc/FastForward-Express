<?php
namespace App\Http\Repos;

use App\AccountUser;
use App\Contact;
use App\EmployeeEmergencyContact;

use Illuminate\Support\Facades\DB;

class ContactRepo {
    public function GetAccountUsers($account_id) {
        $accountUsers = AccountUser::where('account_id', '=', $account_id)
            ->leftJoin('contacts', 'account_users.contact_id', '=', 'contacts.contact_id')
            ->leftJoin('users', 'account_users.user_id', '=', 'users.user_id')
            ->leftJoin('email_addresses', 'account_users.contact_id', '=', 'email_addresses.contact_id')
            ->leftJoin('phone_numbers', 'account_users.contact_id', '=', 'phone_numbers.contact_id')
            ->where('email_addresses.is_primary', true)
            ->where('phone_numbers.is_primary', true)
            ->select('account_users.contact_id',
                    'users.user_id',
                    DB::raw('concat(contacts.first_name, " ", contacts.last_name) as name'),
                    'email_addresses.email as primary_email',
                    'phone_numbers.phone_number as primary_phone',
                    'contacts.position as position',
                    'account_users.is_primary as is_primary');
    
        return $accountUsers->get();
    }

    public function GetById($id) {
        $contact = Contact::where('contact_id', '=', $id)->first();

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

    public function Delete($cid) {
        $contact = $this->GetById($cid);

        $pnRepo = new PhoneNumberRepo();
        $addrRepo = new AddressRepo();
        $emailRepo = new EmailAddressRepo();

        $contact->accounts()->detach();
        $contact->employees()->detach();
        $pnRepo->DeleteByContact($cid);
        $addrRepo->DeleteByContact($cid);
        $emailRepo->DeleteByContact($cid);

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
            throw new Exception('Unable to delete primary emergency contact. Please set another contact to primary, save, and try again.');
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
