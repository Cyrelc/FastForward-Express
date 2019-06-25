<?php
namespace App\Http\Repos;

use App\Account;
use App\AccountUser;
use Illuminate\Support\Facades\DB;

class AccountRepo {

    public function AdjustBalance($account_id, $amount) {
        $account = Account::where('account_id', $account_id)
            ->first();

        $account->account_balance += $amount;

        $account->save();

        return $account;
    }

    public function ListAll() {
        $accounts = Account::leftJoin('accounts as parent', 'accounts.parent_account_id', '=', 'parent.account_id')
            ->leftJoin('addresses as shipping_address', 'accounts.shipping_address_id', '=', 'shipping_address.address_id')
            ->leftJoin('addresses as billing_address', 'accounts.billing_address_id', '=', 'billing_address.address_id')
            ->leftJoin('account_users', function($join) {
                $join->on('account_users.account_id', '=', 'accounts.account_id')
                    ->where('account_users.is_primary', '=', 1);
            })
            ->leftJoin('contacts', 'account_users.contact_id', '=', 'contacts.contact_id')
            ->select('accounts.account_id',
                    'accounts.custom_field as custom_field',
                    'accounts.name',
                    'accounts.account_number',
                    'parent.name as parent_name',
                    'parent.account_id as parent_id',
                    'accounts.active',
                    'accounts.invoice_interval',
                    'shipping_address.name as shipping_address_name',
                    'billing_address.name as billing_address_name',
                    DB::raw('concat(shipping_address.street, " ", shipping_address.street2, ", ", shipping_address.city, ", ", shipping_address.state_province, ", ", shipping_address.country, " ", shipping_address.zip_postal) as shipping_address'),
                    DB::raw('concat(billing_address.street, " ", billing_address.street2, ", ", billing_address.city, ", ", billing_address.state_province, ", ", billing_address.country, " ", billing_address.zip_postal) as billing_address'),
                    DB::raw('concat(contacts.first_name, " ", contacts.last_name) as primary_contact_name'));

        return $accounts->get();
    }

    public function Deactivate($account_id) {
        $account = Account::where('account_id', $account_id)->first();

        $account->active = false;
        $account->save();
        return;
    }

    public function Activate($account_id) {
        $account = Account::where('account_id', $account_id)->first();

        $account->active = true;
        $account->save();
        return;
    }

    public function ListParents() {
        $accounts = Account::where('can_be_parent', '=', true)->get();

        return $accounts;
    }

    public function ListAllWithUninvoicedBillsByInvoiceInterval($invoice_intervals, $start_date, $end_date) {
        $accounts = Account::whereIn('invoice_interval', $invoice_intervals)
                ->where('active', true)
                ->select('accounts.account_id',
                        'accounts.account_number',
                        'accounts.invoice_interval',
                        'accounts.name',
                        DB::raw('(select count(*) from bills where charge_account_id = account_id and "' . $start_date . '" <= DATE(time_pickup_scheduled) and DATE(time_pickup_scheduled) <= "' . $end_date . '" and skip_invoicing = 0 and percentage_complete = 1 and invoice_id IS NULL) as bill_count'),
                        DB::raw('(select count(*) from bills where charge_account_id = account_id and "' . $start_date . '" <= DATE(time_pickup_scheduled) and DATE(time_pickup_scheduled) <= "' . $end_date . '" and skip_invoicing = 0 and percentage_complete != 1) as incomplete_bill_count'),
                        DB::raw('(select count(*) from bills where charge_account_id = account_id and DATE(time_pickup_scheduled) < "' . $start_date . '" and skip_invoicing = 0 and invoice_id IS NULL) as legacy_bill_count'),
                        DB::raw('(select count(*) from bills where charge_account_id = account_id and "' . $start_date . '" <= DATE(time_pickup_scheduled) and DATE(time_pickup_scheduled) <= "' . $end_date . '" and skip_invoicing = 1) as skipped_bill_count')
                )->groupBy('accounts.account_id')
                ->havingRaw('bill_count > 0')
                ->orHavingRaw('incomplete_bill_count > 0')
                ->orHavingRaw('legacy_bill_count > 0')
                ->orHavingRaw('skipped_bill_count > 0');

        return $accounts->get();
    }

    public function GetById($id) {
        $account = Account::where('account_id', '=', $id)->first();

        return $account;
    }

    public function GetNextActiveById($id) {
        $next = Account::where('account_id', '>', $id)
            ->where('active', true)
            ->pluck('account_id')
            ->min();
        return $next;
    }

    public function GetPrevActiveById($id) {
        $prev = Account::where('account_id', '<', $id)
            ->where('active', true)
            ->pluck('account_id')
            ->max();
        return $prev;
    }

    public function GetNameById($account_id) {
        $account = Account::where('account_id', $account_id)->first();

        return $account['name'];
    }

    public function Insert($acct) {
        $new = new Account;

        $new = $new->create($acct);

        return $new;
    }

    public function Update($acct) {
        $old = $this->GetById($acct["account_id"]);

        $old->ratesheet_id = $acct["ratesheet_id"];
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
        $old->min_invoice_amount = $acct['min_invoice_amount'];
        $old->use_parent_ratesheet = $acct['use_parent_ratesheet'];

        $old->save();
    }

    public function GetAccountPrimaryUserId($accountId) {
        $primaryContact = AccountUser::where([['account_id', '=', $accountId], ['is_primary','=','1']])->first();

        return $primaryContact;
    }

    public function ChangePrimary($accountId, $contactId) {
        //Manually do this cause Laravel sucks, ensure parameters are valid
        if ($accountId == null || !is_numeric($accountId) || $accountId <= 0 || $contactId == null || !is_numeric($contactId) || $contactId <= 0) return;
        \DB::update('update account_users set is_primary = 0 where account_id = ' . $accountId . ' and is_primary = 1;');
        \DB::update('update account_users set is_primary = 1 where account_id = ' . $accountId . ' and contact_id = ' . $contactId . ';');
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
