<?php
namespace App\Http\Repos;

use App\Models\AccountUser;
use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\EmailAddress;
use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use App\Models\PhoneNumber;
use DB;

class ActivityLogRepo {
    public function GetAccountActivityLog($accountId) {
        $accountRepo = new AccountRepo();
        $userRepo = new UserRepo();
        $account = $accountRepo->GetById($accountId);
        $activity = ActivityLog::where([['subject_type', 'App\Models\Account'], ['subject_id', $accountId]])
            ->orWhere(function($addresses) use ($account) {
                $addresses->where('subject_type', 'App\Models\Address');
                $addresses->whereIn('subject_id', [$account->billing_address_id, $account->shipping_address_id]);
            })
            ->leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->select(
                'activity_log.updated_at',
                'subject_type',
                'subject_id',
                'description',
                'properties',
                'users.email as user_name'
            )->orderBy('activity_log.updated_at', 'desc');

            return $activity->get();
    }

    public function GetAccountUserActivityLog($contactId) {
        $userId = AccountUser::where('contact_id', $contactId)->pluck('user_id');
        $phoneNumberIds = PhoneNumber::where('contact_id', $contactId)->pluck('phone_number_id');
        $emailAddressIds = EmailAddress::where('contact_id', $contactId)->pluck('email_address_id');

        // Closures, not orWhere([[...], [...]]): since Laravel 11 an array passed to orWhere joins its own conditions
        // with OR, so `subject_type = User OR subject_id = X` matched every user's entries
        $activity = ActivityLog::where([['subject_type', 'App\Models\Contact'], ['subject_id', $contactId]])
            ->orWhere(function($user) use ($userId) {
                $user->where('subject_type', 'App\Models\User')->where('subject_id', $userId[0]);
            })
            ->orWhere(function($phones) use ($phoneNumberIds) {
                $phones->where('subject_type', 'App\Models\PhoneNumber');
                $phones->whereIn('subject_id', $phoneNumberIds->toArray());
            })
            ->orWhere(function($emails) use ($emailAddressIds) {
                $emails->where('subject_type', 'App\Models\EmailAddress');
                $emails->whereIn('subject_id', $emailAddressIds->toArray());
            })->leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->select(
                'activity_log.updated_at',
                'subject_type',
                'subject_id',
                'description',
                'properties',
                'users.email as user_name'
            )->orderBy('activity_log.updated_at', 'desc');

        return $activity->get();
    }

    /**
     * Charges and line items log only the fields that changed (logOnlyDirty), so an "updated" entry usually doesn't say
     * which bill or charge it belongs to - they have to be found by subject_id. Only "created"/"deleted" entries carry
     * every field, so once a charge or line item is gone, its "deleted" entry is the only way back to it.
     *
     * So: collect the subject IDs first (current rows from their tables, deleted ones from their "deleted" entries),
     * then fetch every entry for those IDs. Every lookup uses the activity_log subject / subject_description indexes;
     * an earlier version text-searched all charge and line item entries for the parent ID, which scanned the whole
     * table on every bill load (~5.5s) and never found "updated" entries at all.
     */
    public function GetBillActivityLog($billId) {
        $billId = (int)$billId;
        $user = auth()->user();
        $billRepo = new BillRepo();
        $chargeRepo = new ChargeRepo();

        $bill = $billRepo->GetById($billId);

        // Same scoping as before: GetByBillId limits account users to their own accounts' charges. Deleted charges
        // get the same rule, using the account recorded in their "deleted" entry.
        $myAccounts = $user->accountUsers ? (new AccountRepo())->GetMyAccountIds($user, $user->can('bills.view.basic.children')) : null;
        $chargeIds = $chargeRepo->GetByBillId($billId)->pluck('charge_id')->all();

        $deletedChargeIds = $this->deletedSubjects('App\Models\Charge', 'bill_id', [$billId])
            ->filter(fn($old) => !$myAccounts || in_array($old->charge_account_id ?? null, $myAccounts))
            ->keys()->all();
        $allChargeIds = array_merge($chargeIds, $deletedChargeIds);

        $lineItemIds = $allChargeIds ? DB::table('line_items')->whereIn('charge_id', $allChargeIds)->pluck('line_item_id')->all() : [];
        $deletedLineItemIds = $this->deletedSubjects('App\Models\LineItem', 'charge_id', $allChargeIds)->keys()->all();

        $subjects = [
            'App\Models\Bill' => [$billId],
            'App\Models\Address' => array_filter([$bill->pickup_address_id, $bill->delivery_address_id]),
            'App\Models\Charge' => $allChargeIds,
            'App\Models\LineItem' => array_merge($lineItemIds, $deletedLineItemIds),
        ];

        $activity = ActivityLog::leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->where('log_name', '!=', 'system_debug')
            // Each subject in a closure of its own, nested so the log_name filter applies to all of them. Since
            // Laravel 11, orWhere([[...], [...]]) joins the array's conditions with OR, which once returned every
            // address, charge and line item ever logged.
            ->where(function($query) use ($subjects) {
                foreach(array_filter($subjects) as $type => $ids)
                    $query->orWhere(fn($q) => $q->where('subject_type', $type)->whereIn('subject_id', array_unique($ids)));
            });

        $activity->select(
            'activity_log.updated_at',
            'subject_type',
            'subject_id',
            'users.email as user_name',
            'description',
            'properties'
        )->orderBy('activity_log.updated_at', 'desc');

        return $activity->get();
    }

    /**
     * Deleted rows of $subjectType whose parent ($parentKey) is one of $parentIds, found from their "deleted" log
     * entries, which hold every field - under "old" in current entries, "attributes" in ones logged by older versions
     * of the package. Returns subject_id => those values.
     *
     * The LIKE narrows the search in SQL (the subject_description index keeps it to "deleted" entries only); an ID
     * must end at , or } so that 123 doesn't also match 1234. The decoded value is then checked exactly.
     */
    private function deletedSubjects($subjectType, $parentKey, $parentIds) {
        $parentIds = array_map('intval', $parentIds);
        if(!$parentIds)
            return collect();

        return DB::table('activity_log')
            ->where('subject_type', $subjectType)
            ->where('description', 'deleted')
            ->where(function($query) use ($parentKey, $parentIds) {
                foreach($parentIds as $id)
                    $query->orWhere('properties', 'like', '%"' . $parentKey . '":' . $id . ',%')
                        ->orWhere('properties', 'like', '%"' . $parentKey . '":' . $id . '}%');
            })
            ->get(['subject_id', 'properties'])
            ->mapWithKeys(function($log) {
                $properties = json_decode($log->properties);
                return [$log->subject_id => $properties->old ?? $properties->attributes ?? null];
            })
            ->filter(fn($old) => $old && in_array((int)($old->$parentKey ?? 0), $parentIds, true));
    }

    public function GetEmployeeActivityLog($employeeId) {
        $employee = Employee::find($employeeId);

        $contactIds = EmployeeEmergencyContact::where('employee_id', $employeeId)->pluck('contact_id')->toArray();
        array_push($contactIds, $employee->contact_id);

        $emailIds = EmailAddress::whereIn('contact_id', $contactIds)->pluck('email_address_id')->toArray();
        $phoneIds = PhoneNumber::whereIn('contact_id', $contactIds)->pluck('phone_number_id')->toArray();
        $addressIds = Address::whereIn('contact_id', $contactIds)->pluck('address_id')->toArray();

        $activity = ActivityLog::where([['subject_type', 'App\Models\Employee'], ['subject_id', $employeeId]])
            ->orWhere(function($contacts) use ($contactIds) {
                $contacts->whereIn('subject_type', ['App\Models\Contact']);
                $contacts->whereIn('subject_id', $contactIds);
            })
            ->orWhere(function($addresses) use ($addressIds) {
                $addresses->whereIn('subject_type', ['App\Models\Address']);
                $addresses->whereIn('subject_id', $addressIds);
            })
            ->orWhere(function($emails) use ($emailIds) {
                $emails->whereIn('subject_type', ['App\Models\EmailAddress']);
                $emails->whereIn('subject_id', $emailIds);
            })
            ->orWhere(function($phones) use ($phoneIds) {
                $phones->whereIn('subject_type', ['App\Models\PhoneNumber']);
                $phones->whereIn('subject_id', $phoneIds);
            })
            ->orWhere(function ($users) use ($employee) {
                $users->where('subject_type', 'App\Models\User');
                $users->where('subject_id', $employee->user_id);
            })
            ->leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->select(
                'activity_log.updated_at',
                'subject_type',
                'subject_id',
                'users.email as user_name',
                'description',
                'properties'
            )->orderBy('activity_log.updated_at', 'desc');

        return $activity->get();
    }
}

?>

