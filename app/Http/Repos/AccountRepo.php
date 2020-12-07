<?php
namespace App\Http\Repos;

use App\Account;
use App\AccountUser;
use App\Http\Filters\IsNull;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class AccountRepo {
    public function AdjustBalance($account_id, $amount) {
        $account = Account::where('account_id', $account_id)
            ->first();

        $account->account_balance += $amount;

        $account->save();

        return $account;
    }

    public function CountChildAccounts($accountId) {
        $childCount = Account::where('parent_account_id', $accountId);

        return $childCount->count();
    }

    public function GetAccountIdByAccountNumber($accountNumber) {
        $account = Account::where('account_number', $accountNumber)->first();

        return $account->account_id;
    }

    public function GetAccountList() {
        $accounts = Account::select(
            DB::raw('concat(account_number, " - ", name) as label'),
            'account_id as value'
        );

        return $accounts->get();
    }

    public function GetAccountPrimaryUserId($accountId) {
        $primaryContact = AccountUser::where([['account_id', '=', $accountId], ['is_primary','=','1']])->first();

        return $primaryContact;
    }

    public function GetAccountsReceivable($startDate, $endDate) {
        $accounts = Account::leftjoin('invoices', 'invoices.account_id', '=', 'accounts.account_id')
            ->where('bill_end_date', '>=', $startDate->format('Y-m-01'))
            ->where('bill_end_date', '<=', $endDate->format('Y-m-t'))
            ->select(
                'accounts.account_id',
                'account_number',
                'name',
                DB::raw('sum(total_cost) as total_cost'),
                DB::raw('sum(balance_owing) as balance_owing'),
                'bill_end_date'
            )->groupBy('account_id')
            ->where('total_cost', '>', 0)
            ->orderBy('account_id');

        return $accounts->get();
    }

    public function GetById($id) {
        $account = Account::where('account_id', '=', $id)->first();

        return $account;
    }

    public function GetInvoiceSortOrder($accountId) {
        /* To properly access the sort order we must:
         * 1) Pull it from the account, and parse as JSON
         * 2) Filter any with an empty priority (invalid for this account based on account settings)
         * 3) Sort by priority
         * Then return
         */
        $sortOrder = Account::where('account_id', $accountId)->pluck('invoice_sort_order');
        $sortOrder = json_decode($sortOrder[0]);
        $sortOrder = array_filter($sortOrder, function($var) {return isset($var->priority);});
        usort($sortOrder, function($a, $b) {return ($a->priority - $b->priority);});

        return $sortOrder;
    }

    public function GetNameById($account_id) {
        $account = Account::where('account_id', $account_id)->first();

        return $account['name'];
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

    public function GetParentAccountsList() {
        $parentAccounts = Account::where('can_be_parent', 1)
            ->select(
                DB::raw('concat(account_id, " - ", name) as label'),
                'account_id as value'
            );

        return $parentAccounts->get();
    }

    public function GetSubtotalByField($accountId) {
        $sortOrder = Account::where('account_id', $accountId)->pluck('invoice_sort_order');
        $sortOrder = json_decode(json_decode($sortOrder)[0]);
        $sortOrder = array_filter($sortOrder, function($var) {
            if(isset($var->group_by))
                return filter_var($var->group_by, FILTER_VALIDATE_BOOLEAN);
            return false;
        });

        return array_pop($sortOrder);
    }

    public function Insert($acct) {
        $new = new Account;

        return $new->create($acct);
    }

    public function IsUnique($accountNumber) {
        $result = \DB::select('select name from accounts where account_number ="' . $accountNumber . '";');
        return $result;
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
            ->leftJoin('phone_numbers', function($join) {
                $join->on('phone_numbers.contact_id', '=', 'contacts.contact_id')
                ->where('phone_numbers.is_primary', 1);
            })
            ->select(
                'accounts.account_id',
                'accounts.custom_field as custom_field',
                'accounts.name',
                'accounts.account_number',
                'parent.name as parent_name',
                'parent.account_id as parent_id',
                'accounts.active',
                'accounts.invoice_interval',
                'accounts.ratesheet_id',
                'shipping_address.name as shipping_address_name',
                'shipping_address.formatted as shipping_address',
                'shipping_address.lat as shipping_address_lat',
                'shipping_address.lng as shipping_address_lng',
                'shipping_address.place_id as shipping_address_place_id',
                'billing_address.name as billing_address_name',
                'billing_address.formatted as billing_address',
                'billing_address.lat as billing_address_lat',
                'billing_address.lng as billing_address_lng',
                'billing_address.place_id as billing_address_place_id',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as primary_contact_name'),
                'phone_numbers.phone_number as primary_contact_phone'
            );

        $filteredAccounts = QueryBuilder::for($accounts)
            ->allowedFilters([
                AllowedFilter::exact('account_id', 'accounts.account_id'),
                AllowedFilter::exact('active', 'accounts.active'),
                AllowedFilter::custom('has_parent', new IsNull(), 'accounts.parent_account_id'),
                AllowedFilter::exact('parent_id', 'accounts.parent_account_id'),
                AllowedFilter::exact('invoice_interval')
            ]);

        return $filteredAccounts->get();
    }

    public function ListAllForBillsPage() {
        $accounts = Account::leftjoin('addresses as shipping_address', 'accounts.shipping_address_id', '=', 'shipping_address.address_id')
            ->leftjoin('addresses as billing_address', 'accounts.billing_address_id', '=', 'billing_address.address_id')
            ->select(
                'accounts.name',
                'account_id',
                'account_number',
                'billing_address.lat as billing_address_lat',
                'billing_address.lng as billing_address_lng',
                'billing_address.formatted as billing_address',
                'billing_address.name as billing_address_name',
                'billing_address.place_id as billing_address_place_id',
                'ratesheet_id',
                'shipping_address.lat as shipping_address_lat',
                'shipping_address.lng as shipping_address_lng',
                'shipping_address.formatted as shipping_address',
                'shipping_address.name as shipping_address_name',
                'shipping_address.place_id as shipping_address_place_id',
                'custom_field'
            );

        return $accounts->get();
    }

    public function ListAllWithUninvoicedBillsByInvoiceInterval($invoiceIntervals, $startDate, $endDate) {
        $accounts = Account::leftjoin('selections', 'selections.value', '=', 'accounts.invoice_interval')
            ->whereIn('selections.selection_id', $invoiceIntervals)
            ->where('active', true)
            ->select(
                'accounts.account_id',
                'accounts.account_number',
                'accounts.invoice_interval',
                'accounts.name',
                'selections.selection_id as invoice_interval_selection_id',
                DB::raw('(select count(*) from bills where charge_account_id = account_id and date(time_pickup_scheduled) >= "' . $startDate . '" and date(time_pickup_scheduled) <= "' . $endDate . '" and skip_invoicing = 0 and percentage_complete = 100 and invoice_id IS NULL) as bill_count'),
                DB::raw('(select count(*) from bills where charge_account_id = account_id and date(time_pickup_scheduled) >= "' . $startDate . '" and date(time_pickup_scheduled) <= "' . $endDate . '" and skip_invoicing = 0 and percentage_complete < 100) as incomplete_bill_count'),
                DB::raw('(select count(*) from bills where charge_account_id = account_id and date(time_pickup_scheduled) < "' . $startDate . '" and skip_invoicing = 0 and invoice_id IS NULL) as legacy_bill_count'),
                DB::raw('(select count(*) from bills where charge_account_id = account_id and date(time_pickup_scheduled) >= "' . $startDate . '" and date(time_pickup_scheduled) <= "' . $endDate . '" and skip_invoicing = 1) as skipped_bill_count'
            ))->groupBy('accounts.account_id')
            ->havingRaw('bill_count > 0')
            ->orHavingRaw('incomplete_bill_count > 0')
            ->orHavingRaw('legacy_bill_count > 0')
            ->orHavingRaw('skipped_bill_count > 0');

        return $accounts->get();
    }

    public function ToggleActive($account_id) {
        $account = Account::where('account_id', $account_id)->first();

        $account->active = !$account->active;
        $account->save();
        return;
    }

    public function Update($account) {
        $old = $this->GetById($account["account_id"]);
        $fields = array(
            'account_number',
            'billing_address_id',
            'can_be_parent',
            'custom_field',
            'discount',
            'gst_exempt',
            'invoice_interval',
            'invoice_sort_order',
            'min_invoice_amount',
            'name',
            'parent_account_id',
            'ratesheet_id',
            'send_bills',
            'send_email_invoices',
            'send_paper_invoices',
            'shipping_address_id',
            'start_date',
            'use_parent_ratesheet'
        );

        foreach($fields as $field)
            $old->$field = $account[$field];

        $old->save();

        return $old;
    }
}
