<?php
namespace App\Http\Repos;

use App\Contact;

class ContactRepo {

    public function ListAll() {
        $contacts = Contact::All();

        return $contacts;
    }

    public function GetById($id) {
        $contact = Contact::where('contact_id', '=', $id)->first();

        return $contact;
    }

    public function HandleAccountContacts($contacts, $account_id, $primary_contact_prefix) {
        $accountRepo = new AccountRepo();
        foreach($contacts as $contact) {
            if($contact['action'] == 'delete') {
                $this->Delete($contact['db-id']);
            } else {
                if($contact['action'] == 'create') {
                    $temp = [
                        'first_name'=>$contact['first_name'],
                        'last_name'=>$contact['last_name'],
                        'position'=>$contact['position']
                    ];
                    $contact_id = $this->Insert($temp)['contact_id'];
                    $accountRepo->AddContact($contact_id, $account_id);
                } else if ($contact['action'] =='update') {
                    $contact_id = $this->Update($contact)->contact_id;
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
                if($contact['prefix'] == $primary_contact_prefix)
                    $accountRepo->ChangePrimary($account_id, $contact_id);
            }
        }
    }

    public function HandleEmergencyContacts($contacts, $employee_id, $primary_emergency_contact_prefix) {
        $emergencyContactIds = [];
        $employeeRepo = new EmployeeRepo();
        $addressRepo = new AddressRepo();
        foreach($contacts as $contact) {
            if($contact['action'] == 'delete') {
                $this->Delete($contact['db-id']);
            } else {
                if($contact['action'] == 'create') {
                    $temp = [
                        'first_name'=>$contact['first_name'],
                        'last_name'=>$contact['last_name'],
                        'position'=>$contact['position']
                    ];
                    $contact_id = $this->Insert($temp)['contact_id'];
                    array_push($emergencyContactIds, $contact_id);
                    $contact['address']['contact_id'] = $contact_id;
                    $addressRepo->Insert($contact['address']);
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
        return $emergencyContactIds;
    }

    public function Insert($contact) {
        $new = new Contact;

        $new = $new->create($contact);

        return $new;
    }

    public function Update($contact) {
        $old = $this->GetById($contact['db-id']);

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
