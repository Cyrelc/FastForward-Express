<?php
namespace App\Http\Repos;

use App\Account;
use App\AccountContact;

class AccountRepo {

    public function ListAll() {
        $accounts = Account::All();

        return $accounts;
    }

    public function ListParents() {
        $accounts = Account::where('can_be_parent', '=', true)->get();

        return $accounts;
    }

    public function GetById($id) {
        $account = Account::where('account_id', '=', $id)->first();

        return $account;
    }

    public function Insert($acct, $primaryId, $secondaryIds) {
        $new = new Account;

        $new = $new->create($acct);

        $new->contacts()->attach($primaryId);

        foreach($secondaryIds as $secondaryId)
            $new->contacts()->attach($secondaryId);

        return $new;
    }

    public function Update($acct) {
        $old = $this->GetById($acct["account_id"]);

        $old->rate_type_id = $acct["rate_type_id"];
        $old->billing_address_id = $acct["billing_address_id"];
        $old->shipping_address_id = $acct["shipping_address_id"];
        $old->account_number = $acct["account_number"];
        $old->invoice_interval = $acct["invoice_interval"];
        $old->invoice_comment = $acct["invoice_comment"];
        $old->stripe_id = $acct["stripe_id"];
        $old->name = $acct["name"];
        $old->start_date = $acct["start_date"];
        $old->send_bills = $acct["send_bills"];
        $old->is_master = $acct["is_master"];
        $old->parent_account_id = $acct["parent_account_id"];
        $old->gets_discount = $acct["gets_discount"];
        $old->discount = $acct["discount"];
        $old->gst_exempt = $acct["gst_exempt"];
        $old->charge_interest = $acct["charge_interest"];
        $old->fuel_surcharge = $acct["fuel_surcharge"];
        $old->can_be_parent = $acct["can_be_parent"];
        $old->uses_custom_field = $acct["uses_custom_field"];
        $old->custom_field = $acct["custom_field"];
        $old->active = $acct["active"];

        $old->save();
    }

    public function ListAccountContacts($accountId) {
        $accountContacts = AccountContact::where('account_id', '=', $accountId)->get();

        return $accountContacts;
    }

    public function ChangePrimary($accountId, $contactId) {
        //dd($contactId);
        //Manually do this cause Laravel sucks, ensure parameters are valid
        if ($accountId == null || !is_numeric($accountId) || $accountId <= 0 || $contactId == null || !is_numeric($contactId) || $contactId <= 0) return;
        \DB::update('update account_contacts set is_primary = 0 where account_id = ' . $accountId . ' and is_primary = 1');
        \DB::update('update account_contacts set is_primary = 1 where account_id = ' . $accountId . ' and contact_id = ' . $contactId);
    }
}
