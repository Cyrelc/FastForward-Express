<?php

namespace App\Http\Repos;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Account;
use App\Bill;
use App\Charge;
use App\AccountUser;
use App\Models\EmailAddress;
use App\Employee;
use App\Invoice;
use App\Manifest;

class SearchRepo {
    private $user;

    public function __construct() {
        $this->user = Auth::user() ?? auth('sanctum')->user();
    }

    public function AccountSearch($searchTerm) {
        if($this->user->cannot('viewAny', Account::class))
            return [];

        $accountRepo = new AccountRepo();

        $myAccounts = $this->user->accountUsers ? $accountRepo->GetMyAccountIds($this->user, $this->user->can('accounts.view.basic.children')) : null;

        $accounts = Account::where(function($query) use ($searchTerm) {
                $words = preg_split('/[\s+\-<>()~*"@]+/', $searchTerm, -1, PREG_SPLIT_NO_EMPTY);
                $words = array_filter($words, function($word) {
                    return strlen($word) > 2;
                });
                $words = '+' . implode('* +', $words) . '*';

                $query->where('account_id', $searchTerm)
                ->orWhere('account_number', 'like', '%' . $searchTerm . '%')
                ->orWhereRaw('MATCH(name) against (? in boolean mode)', [$words]);
            })->select(
                'account_number',
                DB::raw('CONCAT("/accounts/", account_id) as link'),
                'name',
                'account_id as object_id',
                DB::raw('"Account" as result_type')
            );

        if($myAccounts)
            $accounts->whereIn('account_id', $myAccounts);

        return $accounts->get()->toArray();
    }

    public function AccountUserSearch($searchTerm) {
        if($this->user->employee && $this->user->cannot('viewAny', Account::class))
            return [];

        $accountRepo = new AccountRepo();

        $myAccounts = $this->user->accountUsers ? $accountRepo->GetMyAccountIds($this->user, $this->user->can('accounts.view.basic.children')) : null;

        $accountUsers = EmailAddress::leftJoin('contacts', 'contacts.contact_id', 'email_addresses.contact_id')
            ->rightJoin('account_users', 'account_users.contact_id', 'contacts.contact_id')
            ->where(function($query) use ($searchTerm) {
                $query->where('email_addresses.email', 'like', '%' . $searchTerm . '%')
                ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'like', '%' . $searchTerm . '%')
                ->orWhere('preferred_name', 'like', '%' . $searchTerm . '%');
            })->select(
                'account_id',
                'email_addresses.email',
                DB::raw('CONCAT("/accounts/", account_id, "#users") as link'),
                DB::raw('CONCAT(TRIM(first_name), " ", TRIM(last_name)) as name'),
                'account_id as object_id',
                DB::raw('"Account User" as result_type'),
            );

        if($myAccounts)
            $accountUsers->whereIn('account_id', $myAccounts);

        return $accountUsers->get()->toArray();
    }

    public function BillSearch($searchTerm) {
        if($this->user->cannot('viewAny', Bill::class))
            return [];

        $accountRepo = new AccountRepo();
        $myAccounts = $this->user->accountUsers ? $accountRepo->GetMyAccountIds($this->user, $this->user->can('bills.view.basic.children')) : null;

        $bills = Charge::leftJoin('bills', 'bills.bill_id', 'charges.bill_id')
            ->leftJoin('accounts as charge_account', 'charge_account.account_id', 'charges.charge_account_id')
            ->leftJoin('accounts as delivery_account', 'delivery_account.account_id', 'bills.delivery_account_id')
            ->leftJoin('accounts as pickup_account', 'pickup_account.account_id', 'bills.pickup_account_id')
            ->where(function($query) use ($searchTerm) {
                $query->where('bills.bill_id', $searchTerm)
                ->orWhere('bill_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('charge_reference_value', 'like', '%' . $searchTerm . '%')
                ->orWhere('pickup_reference_value', 'like', '%' . $searchTerm . '%')
                ->orWhere('delivery_reference_value', 'like', '%' . $searchTerm . '%');
            })->select(
                'bill_number',
                DB::raw('cast(bills.bill_id as char(255)) as name'),
                'charge_reference_value',
                'charge_account.custom_field as charge_reference_field_name',
                'delivery_reference_value',
                'delivery_account.custom_field as delivery_reference_field_name',
                DB::raw('CONCAT("/bills/", bills.bill_id) as link'),
                'bills.bill_id as object_id',
                'pickup_reference_value',
                'pickup_account.custom_field as pickup_reference_field_name',
                DB::raw('"Bill" as result_type'),
            );

        if($myAccounts)
            $bills->whereIn('charges.charge_account_id', $myAccounts);

        return $bills->get()->toArray();
    }

    public function EmployeeSearch($searchTerm) {
        if($this->user->cannot('viewAny', Employee::class))
            return [];

        $employees =
            EmailAddress::leftJoin('contacts', 'contacts.contact_id', 'email_addresses.contact_id')
            ->rightJoin('employees', 'employees.contact_id', 'contacts.contact_id')
            ->leftJoin('users', 'users.user_id', 'employees.user_id')
            ->where('email_addresses.email', 'like', '%' . $searchTerm . '%')
            ->orWhere('employee_id', $searchTerm)
            ->orWhere(DB::raw('CONCAT(TRIM(first_name), " ", TRIM(last_name))'), 'like', '%' . $searchTerm . '%')
            ->orWhere('preferred_name', 'like', '%' . $searchTerm . '%')
            ->select(
                'email_addresses.email',
                DB::raw('CONCAT("/employees/", employee_id) as link'),
                DB::raw('CONCAT(first_name, " ", last_name) as name'),
                'employee_id as object_id',
                DB::raw('"Employee" as result_type'),
            );

        return $employees->get()->toArray();
    }

    public function GlobalSearch($searchTerm) {
        return array_merge(
            $this->AccountSearch($searchTerm),
            $this->AccountUserSearch($searchTerm),
            $this->BillSearch($searchTerm),
            $this->EmployeeSearch($searchTerm),
            $this->InvoiceSearch($searchTerm),
            $this->ManifestSearch($searchTerm)
        );
    }

    public function InvoiceSearch($searchTerm) {
        if($this->user->cannot('viewAny', Invoice::class))
            return [];

        $invoices = Invoice::where('invoice_id', $searchTerm)
            ->select(
                'account_id',
                'invoice_id as object_id',
                DB::raw('concat("/invoices/", invoice_id) as link'),
                DB::raw('"Invoice" as result_type'),
                DB::raw('cast(invoice_id as char(255)) as name')
            );

        return $invoices->get()->toArray();
    }

    public function ManifestSearch($searchTerm) {
        if($this->user->cannot('viewAny', Manifest::class))
            return [];

        $manifests = Manifest::where('manifest_id', $searchTerm)
            ->select(
                'employee_id',
                DB::raw('concat("/manifests/", manifest_id) as link'),
                'manifest_id as object_id',
                DB::raw('"Manifest" as result_type'),
                DB::raw('cast(manifest_id as char(255)) as name')
            );

        return $manifests->get()->toArray();
    }
}

?>
