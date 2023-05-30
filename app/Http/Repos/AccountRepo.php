<?php
namespace App\Http\Repos;

use App\Account;
use App\AccountUser;
use App\AccountInvoiceSortOrder;
use App\InvoiceSortOption;
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

    public function ConvertInvoiceSortOrder($accountId) {
        $account = Account::where('account_id', $accountId)->first();

        $invoiceSortOrder = json_decode($account->invoice_sort_order);

        usort($invoiceSortOrder, fn($a, $b) => intval($a->priority) > intval($b->priority));
        foreach($invoiceSortOrder as $index => $sortEntry) {
            $new = new AccountInvoiceSortOrder;

            try {
                $new->create([
                    'subtotal_by' => filter_var($sortEntry->group_by, FILTER_VALIDATE_BOOLEAN),
                    'invoice_sort_option_id' => $sortEntry->invoice_sort_option_id,
                    'priority' => $sortEntry->priority ?? $index,
                    'account_id' => $accountId,
                ]);
            } catch (\Exception $e) {
                dd($accountId, $sortEntry, $e);
            }
        }
    }

    public function GetAccountIdByAccountNumber($accountNumber) {
        $account = Account::where('account_number', $accountNumber)->first();

        return $account->account_id;
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
                'bill_end_date',
                DB::raw('"Account" as type')
            )->groupBy('account_id')
            ->where('total_cost', '>', 0)
            ->orderBy('account_id');

        return $accounts->get();
    }

    /**
     * Gets a single account object and returns it
     * WARNING - To be used internally ONLY - returning this to the client frontend could result in unintentionally exposed data
     * Wherever possible, use GetByIdWithPermissions()
     * @param accountId $id
     */
    public function GetById($accountId) {
        $account = Account::where('account_id', $accountId);

        return $account->first();
    }

    public function GetByInvoiceInterval($invoiceIntervals) {
        $accounts = Account::leftJoin('selections', 'selections.value', '=', 'accounts.invoice_interval')
            ->whereIn('selections.selection_id', $invoiceIntervals)
            ->where('active', true)
            ->select('*', 'accounts.name as name');

        return $accounts->get();
    }

    /**
     * Gets a single account object and subsequent values and returns it
     * Levels are determined by a user's permissions: For example a system level admin might see and be able to modify every attribute, but
     * an account level user or admin would receive drastically reduced information
     * @param accountId $id
     * @param permissions $permissions - array of permissions describing account accessibility level, retrieved from the Permission Model Factory
     * 
     */
    public function GetByIdWithPermissions($accountId, $permissions) {
        $account = Account::where('account_id', '=', $accountId);

        $account->select(
            array_merge(
                $permissions['viewPayments'] ? Account::$accountingFields : [],
                $permissions['editAdvanced'] ? Account::$advancedFields : [],
                Account::$basicFields,
                Account::$invoicingFields,
                Account::$readOnlyFields
            )
        );

        return $account->first();
    }

    /**
     * Gets the list of child accounts based on accountId
     * @param unsignedInt $accountId
     * @return array $accounts
     */
    public function GetChildAccountList($accountId) {
        $account = Account::where('parent_account_id', $accountId)
            ->select(
                'name',
                'account_id',
                'account_number'
            );

        return $account->get();
    }

    /**
     * Gathers all sort options for account invoices
     * Importantly, this starts with InvoiceSortOption and then joins the existing entries
     * In this way, we both get entries for accounts without any, and if new options are added, they will still be merged with default values
     */
    public function GetInvoiceSortOrder($accountId = null) {
        $sortOrder = DB::table('invoice_sort_options');

        if($accountId)
            $sortOrder->leftJoin('account_invoice_sort_order', 'account_invoice_sort_order.invoice_sort_option_id', 'invoice_sort_options.invoice_sort_option_id')
                ->where('account_id', $accountId)
                ->orderBy('priority');

        return $sortOrder->get();
    }

    public function GetMyAccountIds($user, $withChildren = false) {
        $accountUsers = $user->accountUsers;
        $accountIds = [];
        foreach($accountUsers as $accountUser)
            $accountIds[] = $accountUser->account_id;

        $accounts = Account::whereIn('account_id', $accountIds);
        if($withChildren)
            $accounts->orWhereIn('parent_account_id', $accountIds);

        return $accounts->pluck('account_id')->toArray();
    }

    public function GetMyAccountsStructured($user) {
        $accountUsers = $user->accountUsers;
        $accounts = [];
        foreach($accountUsers as $accountUser)
            $accounts[] = [
                'account' => Account::where('account_id', $accountUser->account_id)->select('name', 'account_id', 'account_number')->first(),
                'children' => Account::where('parent_account_id', $accountUser->account_id)->select('name', 'account_id', 'account_number')->get()
            ];

        return $accounts;
    }

    public function GetMyRatesheetIds($accountIds) {
        $ratesheetIds = Account::whereIn('account_id', $accountIds);

        return $ratesheetIds->pluck('ratesheet_id');
    }

    public function GetWithUninvoicedLineItems($invoiceIntervals, $startDate, $endDate) {
        $accounts = Account::leftJoin('selections', 'selections.value', '=', 'accounts.invoice_interval')
            ->leftJoin('accounts as parent_account', 'parent_account.account_id', '=', 'accounts.parent_account_id')
            ->whereIn('selections.selection_id', $invoiceIntervals)
            ->where('accounts.active', true)
            ->select(
                'accounts.account_id',
                'accounts.account_number',
                'selections.name as invoice_interval',
                'accounts.name',
                DB::raw('case when accounts.parent_account_id is not null then concat(parent_account.account_number, " - ", parent_account.name) when accounts.can_be_parent = 1 then concat (accounts.account_number, " - ", accounts.name) else "None" end as parent_account'),
                'selections.selection_id as invoice_interval_selection_id',
                DB::raw(
                    '(select count(distinct bills.bill_id) from line_items
                    left join charges on charges.charge_id = line_items.charge_id
                    left join bills on bills.bill_id = charges.bill_id
                        where line_items.invoice_id is null
                        and accounts.account_id = charges.charge_account_id
                        and bills.percentage_complete = 100
                        and date(time_pickup_scheduled) between cast("' . $startDate . '" as date) and cast("' . $endDate . '" as date))
                    as valid_bill_count'
                ),
                DB::raw(
                    '(select count(distinct bills.bill_id) from line_items
                    left join charges on charges.charge_id = line_items.charge_id
                    left join bills on bills.bill_id = charges.bill_id
                        where line_items.invoice_id is null
                        and accounts.account_id = charges.charge_account_id
                        and bills.percentage_complete = 100
                        and date(time_pickup_scheduled) < cast("' . $startDate . '" as date))
                    as legacy_bill_count'
                ),
                DB::raw(
                    '(select count(distinct bills.bill_id) from line_items
                    left join charges on charges.charge_id = line_items.charge_id
                    left join bills on bills.bill_id = charges.bill_id
                        where line_items.invoice_id is null
                        and accounts.account_id = charges.charge_account_id
                        and bills.percentage_complete = 100
                        and date(time_pickup_scheduled) between cast("' . $startDate . '" as date) and cast("' . $endDate . '" as date)
                        and skip_invoicing = true)
                    as skipped_bill_count'
                ),
                DB::raw(
                    '(select count(distinct bills.bill_id) from charges
                    left join line_items on line_items.charge_id = charges.charge_id
                    left join bills on bills.bill_id = charges.bill_id
                        where (line_items.invoice_id is null or line_items.price = 0)
                        and accounts.account_id = charges.charge_account_id
                        and bills.percentage_complete < 100
                        and date(time_pickup_scheduled) between cast("' . $startDate . '" as date) and cast("' . $endDate . '"as date))
                    as incomplete_bill_count'
                )
            )->groupBy('accounts.account_id')
            ->havingRaw('valid_bill_count > 0')
            ->orHavingRaw('legacy_bill_count > 0')
            ->orHavingRaw('skipped_bill_count > 0')
            ->orHavingRaw('incomplete_bill_count > 0');

        return $accounts->get();
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

    public function GetPrevActiveById($accountId) {
        $prev = Account::where('account_id', '<', $accountId)
            ->where('active', true)
            ->pluck('account_id')
            ->max();
        return $prev;
    }

    public function GetParentAccountsList($accountId = null) {
        $parentAccounts = Account::where('can_be_parent', 1)
            ->select(
                DB::raw('concat(account_number, " - ", name) as label'),
                'account_id as value'
            );
        if($accountId)
            $parentAccounts->where('account_id', $accountId);

        return $parentAccounts->get();
    }

    public function GetSubtotalByField($accountId) {
        $subtotalByField = AccountInvoiceSortOrder::leftJoin('invoice_sort_options', 'invoice_sort_options.invoice_sort_option_id', 'account_invoice_sort_order.invoice_sort_option_id')
            ->where('account_id', $accountId)
            ->where('subtotal_by', 1);

        return $subtotalByField->first();
    }

    public function Insert($acct) {
        $new = new Account;
        $result = $new->create($acct);

        return $result;
    }

    public function IsNameUnique($accountNumber) {
        $result = \DB::select('select name from accounts where account_number ="' . $accountNumber . '";');
        return $result;
    }

    public function List($user, $withChildren = false) {
        $accounts = Account::select(
            DB::raw('concat(account_number, " - ", name) as label'),
            'account_id as value'
        );

        if($user && $user->accountUsers)
            $accounts->whereIn('account_id', $this->GetMyAccountIds($user, $withChildren));

        return $accounts->get();
    }

    public function ListAll($user, $withChildren = false) {
        $accounts = Account::leftJoin('accounts as parent', 'accounts.parent_account_id', '=', 'parent.account_id')
            ->leftJoin('addresses as shipping_address', 'accounts.shipping_address_id', '=', 'shipping_address.address_id')
            ->leftJoin('addresses as billing_address', 'accounts.billing_address_id', '=', 'billing_address.address_id')
            ->leftJoin('selections as invoice_intervals', 'invoice_intervals.value', '=', 'accounts.invoice_interval')
            ->leftJoin('account_users', function($join) {
                $join->on('account_users.account_id', '=', 'accounts.account_id')
                    ->where('account_users.is_primary', '=', 1);
            })
            ->leftJoin('contacts', 'account_users.contact_id', '=', 'contacts.contact_id')
            ->leftJoin('phone_numbers', function($join) {
                $join->on('phone_numbers.contact_id', '=', 'contacts.contact_id')
                ->where('phone_numbers.is_primary', 1);
            })->select(
                'accounts.account_id',
                'accounts.custom_field as custom_field',
                'accounts.name',
                'accounts.account_number',
                'parent.name as parent_name',
                'parent.account_id as parent_id',
                'accounts.active',
                'invoice_intervals.name as invoice_interval',
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
                'phone_numbers.phone_number as primary_contact_phone',
                'accounts.start_date as start_date',
                'accounts.created_at as created_at'
            );

        if($user && $user->accountUsers)
            $accounts->whereIn('accounts.account_id', $this->GetMyAccountIds($user, $withChildren));

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

    public function ListForBillsPage($user, $withChildren = false) {
        $accounts = Account::leftjoin('addresses as shipping_address', 'accounts.shipping_address_id', '=', 'shipping_address.address_id')
            ->leftjoin('addresses as billing_address', 'accounts.billing_address_id', '=', 'billing_address.address_id')
            ->select(
                DB::raw('concat(accounts.account_number, " - ", accounts.name) as label'),
                'accounts.account_id as value',
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
                'custom_field',
                'is_custom_field_mandatory'
            );

        if($user->cannot('createBasicAnyAccount', Bill::class))
            $accounts->whereIn('account_id', $this->GetMyAccountIds($user, $withChildren));

        return $accounts->get();
    }

    public function StoreInvoiceSortOrder($sortOrder) {
        foreach($sortOrder as $entry) {
            if($entry['account_invoice_sort_order_id']) {
                $old = AccountInvoiceSortOrder::where('account_invoice_sort_order_id', $entry['account_invoice_sort_order_id'])
                    ->update($entry);
            } else {
                $new = new AccountInvoiceSortOrder();
                $new->create($entry);
            }
        }
    }

    public function ToggleActive($account_id) {
        $account = Account::where('account_id', $account_id)->first();

        $account->active = !$account->active;
        $account->save();
        return;
    }

    public function Update($account, $accountPermissions) {
        $old = $this->GetById($account['account_id']);

        if($accountPermissions['editAdvanced'])
            foreach(Account::$advancedFields as $advancedField)
                $old->$advancedField = $account[$advancedField];

        if($accountPermissions['editBasic'])
            foreach(Account::$basicFields as $basicField)
                $old->$basicField = $account[$basicField];

        if($accountPermissions['editInvoicing'])
            foreach(Account::$invoicingFields as $invoicingField)
                $old->$invoicingField = $account[$invoicingField];

        $old->save();

        return $old;
    }

    /**
     * A private function to filter "list" style requests and return only those accounts falling under the users current level of permissions
     */
    private function restrictByAccountAndChildren($query, $accountUsers, $children = false) {
        $children = $this->GetChildAccountList($accountId);
        $accountIds = [$accountId];

        foreach($children as $child)
            array_push($accountIds, $child->account_id);

        $query->whereIn('account_id', $accountIds);

        return $query;
    }
}
