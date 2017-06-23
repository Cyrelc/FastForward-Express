<?php

namespace App\Http\Collectors;


class ContactCollector {
    public function Collect($req, $prefix){
        return [
            'first_name'=>$req->input($prefix . '-first-name'),
            'last_name'=>$req->input($prefix . '-last-name')
        ];
    }

    public function Remerge($req, $model) {
        $newContacts = [];
        $deleteContacts = [];

        $primary = $req->old('contact-action-change-primary') === null ? -1 : $req->old('contact-action-change-primary');

        if ($req->old('contact-action-add') !== null) {
            $newContacts = $req->old('contact-action-add');

            if (!is_array($newContacts))
                $newContacts = [$newContacts];
        }

        if ($req->old('contact-action-delete') !== null) {
            $deleteContacts = $req->old('contact-action-delete');

            if (!is_array($deleteContacts))
                $deleteContacts = [$deleteContacts];
        }

        $pnsToDelete = $req->old('pn-action-delete');
        $emsToDelete = $req->old('em-action-delete');

        if ($pnsToDelete === null)
            $pnsToDelete = [];

        if ($emsToDelete === null)
            $emsToDelete = [];
        for($i = 0; $i < count($model->account->contacts); $i++) {
            $contactId = $model->account->contacts[$i]->contact_id;

            if (in_array($contactId, $newContacts))
                $model->account->contacts[$i]->is_new = true;
            else
                $model->account->contacts[$i]->is_new = false;

            if (in_array($contactId, $deleteContacts))
                $model->account->contacts[$i]->delete = true;
            else
                $model->account->contacts[$i]->delete = false;

            if ($primary !== -1) {
                if ($contactId == $primary)
                    $model->account->contacts[$i]->is_primary = true;
                else
                    $model->account->contacts[$i]->is_primary = false;
            }

            if ($req->old("contact-" . $contactId . "-first-name") !== null)
                $model->account->contacts[$i]->first_name = $req->old("contact-" . $contactId . "-first-name");

            if ($req->old("contact-" . $contactId . "-last-name") !== null)
                $model->account->contacts[$i]->last_name = $req->old("contact-" . $contactId . "-last-name");

            if ($req->old("contact-" . $contactId . "-phone1") !== null)
                $model->account->contacts[$i]->primaryPhone->phone_number = $req->old("contact-" . $contactId . "-phone1");

            if ($req->old("contact-" . $contactId . "-phone1-ext") !== null)
                $model->account->contacts[$i]->primaryPhone->extension_number = $req->old("contact-" . $contactId . "-phone1-ext");

            if ($req->old("contact-" . $contactId . "-email1") !== null)
                $model->account->contacts[$i]->primaryEmail->email_address = $req->old("contact-" . $contactId . "-email1");

            if ($req->old('pn-action-add-' . $contactId) !== null || $req->input('contact-' . $contactId . '-phone2-id') !== null) {
                $model->account->contacts[$i]->secondaryPhone = new \App\PhoneNumber();

                if ($req->old('pn-action-add-' . $contactId) !== null) {
                    $model->account->contacts[$i]->secondaryPhone->is_new = true;
                }

                $model->account->contacts[$i]->secondaryPhone->phone_number_id = $req->old('contact-' . $contactId . '-phone2-id');

                if ($model->account->contacts[$i]->secondaryPhone->phone_number_id !== null && in_array($model->account->contacts[$i]->secondaryPhone->phone_number_id, $pnsToDelete))
                    $model->account->contacts[$i]->secondaryPhone->delete = true;
                else
                    $model->account->contacts[$i]->secondaryPhone->delete = false;

                if ($req->old('contact-' . $contactId . '-phone2') !== null)
                    $model->account->contacts[$i]->secondaryPhone->phone_number = $req->old('contact-' . $contactId . '-phone2');

                $model->account->contacts[$i]->secondaryPhone->extension_number = $req->old('contact-' . $contactId . '-phone2-ext');
            }

            if ($req->old('em-action-add-' . $contactId) !== null || $req->old('contact-' . $contactId . '-email2-id') !== null) {
                $model->account->contacts[$i]->secondaryEmail = new \App\EmailAddress();

                if ($req->old('em-action-add-' . $contactId) !== null)
                    $model->account->contacts[$i]->secondaryEmail->is_new = true;

                $model->account->contacts[$i]->secondaryEmail->email_address_id = $req->old('contact-' . $contactId . '-email2-id');

                if ($model->account->contacts[$i]->secondaryEmail->email_address_id !== null && in_array($model->account->contacts[$i]->secondaryEmail->email_address_id, $emsToDelete))
                    $model->account->contacts[$i]->secondaryEmail->delete = true;
                else
                    $model->account->contacts[$i]->secondaryEmail->delete = false;

                if ($req->old('contact-' . $contactId . '-email2') !== null)
                    $model->account->contacts[$i]->secondaryEmail->email = $req->old('contact-' . $contactId . '-email2');
            }
        }

        return $model;
    }

    public function CollectPhoneNumber($req, $prefix, $isPrimary, $contactId) {
        return [
            'phone_number_id' => $req->input('contact-' . $contactId . ($isPrimary ? '-phone1' : '-phone2') . '-id'),
            'phone_number' => $req->input($prefix),
            'extension_number' => $req->input($prefix . '-ext'),
            'is_primary' => $isPrimary,
            'contact_id' => $contactId
        ];
    }

    public function CollectEmail($req, $prefix, $isPrimary, $contactId) {
        return [
            'email_address_id'=>$req->input($prefix . ($isPrimary ? '-email1-' : '-email2-') . 'id'),
            'email'=>$req->input($prefix . ($isPrimary ? '-email1' : '-email2')),
            'contact_id'=>$contactId,
            'is_primary'=>$isPrimary
        ];
    }


}
