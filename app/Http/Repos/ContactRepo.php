<?php
namespace App\Http\Repos;

use App\Contact;
use App\AccountUser;

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

    public function HandleEmergencyContacts($contacts, $employee_id, $primary_emergency_contact_prefix) {
        $employeeRepo = new EmployeeRepo();
        $addressRepo = new AddressRepo();
        foreach($contacts as $contact) {
            if($contact['action'] == 'delete' && $contact['db-id'] != '') {
                $this->Delete($contact['db-id']);
            } else {
                if($contact['action'] == 'create') {
                    $temp = [
                        'first_name'=>$contact['first_name'],
                        'last_name'=>$contact['last_name'],
                        'position'=>$contact['position']
                    ];
                    $contact_id = $this->Insert($temp)['contact_id'];
                    $contact['address']['contact_id'] = $contact_id;
                    $addressRepo->Insert($contact['address']);
                    $employeeRepo->AddEmergencyContact($employee_id, $contact_id);
                } else if ($contact['action'] =='update') {
                    $contact_id = $this->Update($contact)->contact_id;
                    $contact['address']['contact_id'] = $contact_id;
                    $addressRepo->Update($contact['address']);
                }
                $phoneRepo = new PhoneNumberRepo();
                foreach($contact['phone_numbers'] as $phone) {
                    $phone['contact_id'] = $contact_id;
                    $phoneRepo->Handle($phone, $contact_id);
                }
                $emailRepo = new EmailAddressRepo();
                foreach($contact['emails'] as $email) {
                    $email['contact_id'] = $contact_id;
                    if(isset($email['email_address_id']))
                        $emailRepo->Update($email);
                    else
                        $emailRepo->Insert($email);
                }
                if($contact['prefix'] == $primary_emergency_contact_prefix)
                    $employeeRepo->ChangePrimary($employee_id, $contact_id);
            }
        }
    }

    public function Insert($contact) {
        $new = new Contact;

        $new = $new->create($contact);

        return $new;
    }

    public function Update($contact) {
        $old = $this->GetById($contact['contact_id']);

        $old->first_name = $contact["first_name"];
        $old->last_name = $contact["last_name"];
        $old->position = $contact["position"];

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
}
