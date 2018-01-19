<?php
namespace App\Http\Repos;

use App\Account;
use App\AccountContact;
use Illuminate\Support\Facades\DB;

class AccountRepo {

    public function ListAll() {
        $accounts = Account::All();

        return $accounts;
    }

    public function ListParents() {
        $accounts = Account::where('can_be_parent', '=', true)->get();

        return $accounts;
    }

    public function ListAllWithUninvoicedBillsByInvoiceInterval($invoice_interval, $start_date, $end_date) {
        $accounts = DB::select('SELECT a.account_id, a.name, count(b.bill_id) as bill_count FROM bills b inner join accounts a on a.account_id = b.charge_account_id where a.invoice_interval = "' . $invoice_interval . '" and a.active = 1 and b.is_invoiced = 0 and b.skip_invoicing = 0 and b.date >= "' . $start_date . '" and b.date <= "' . $end_date . '" group by a.account_id order by a.name');

        return $accounts;
    }

    public function GetById($id) {
        $account = Account::where('account_id', '=', $id)->first();

        return $account;
    }

    public function Insert($acct) {
        $new = new Account;

        $new = $new->create($acct);

        return $new;
    }

    public function Update($acct) {
        $old = $this->GetById($acct["account_id"]);

        $old->rate_type_id = $acct["rate_type_id"];
        $old->billing_address_id = $acct["billing_address_id"];
        $old->shipping_address_id = $acct["shipping_address_id"];
        $old->account_number = $acct["account_number"];
        $old->invoice_interval = $acct["invoice_interval"];
        $old->stripe_id = $acct["stripe_id"];
        $old->name = $acct["name"];
        $old->start_date = $acct["start_date"];
        $old->send_bills = $acct["send_bills"];
        $old->send_invoices = $acct["send_invoices"];
        $old->has_parent = $acct["has_parent"];
        $old->parent_account_id = $acct["parent_account_id"];
        $old->has_discount = $acct["has_discount"];
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

    public function GetAccountPrimaryContactId($accountId) {
        $primaryContact = AccountContact::where([['account_id', '=', $accountId], ['is_primary','=','1']])->first();

        return $primaryContact;
    }

    public function ChangePrimary($accountId, $contactId) {
        //Manually do this cause Laravel sucks, ensure parameters are valid
        if ($accountId == null || !is_numeric($accountId) || $accountId <= 0 || $contactId == null || !is_numeric($contactId) || $contactId <= 0) return;
        \DB::update('update account_contacts set is_primary = 0 where account_id = ' . $accountId . ' and is_primary = 1;');
        \DB::update('update account_contacts set is_primary = 1 where account_id = ' . $accountId . ' and contact_id = ' . $contactId . ';');
    }

    public function IsUnique($accountNumber) {
        $result = \DB::select('select name from accounts where account_number ="' . $accountNumber . '";');
        return $result;
    }

    public function AddContact($contactId, $accountId) {
        $account = $this->GetById($accountId);
        $account->contacts()->attach($contactId, ['is_primary' => 'false']);
    }

    public function UpdateInvoiceComment($comment, $accountId) {
        $account = $this->GetById($accountId);
        $account->invoice_comment = $comment;

        $account->save();
    }
}
