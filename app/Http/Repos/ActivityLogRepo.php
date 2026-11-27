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

    public function GetBillActivityLog($billId) {
        $billRepo = new BillRepo();
        $chargeRepo = new ChargeRepo();

        $bill = $billRepo->GetById($billId);
        $charges = $chargeRepo->GetByBillId($billId);

        // Each subject in a closure of its own. Since Laravel 11, orWhere([[...], [...]]) joins the array's conditions
        // with OR, so `subject_type = Address OR subject_id = X` returned every address, charge and line item ever
        // logged - hundreds of thousands of rows, exhausting memory on every bill load.
        // An ID in properties must end at , or } so that bill 123 doesn't also match bill 1234.
        $hasId = fn($query, $key, $id) => $query->where(function($id_) use ($key, $id) {
            $id_->where('properties', 'like', '%"' . $key . '":' . $id . ',%')
                ->orWhere('properties', 'like', '%"' . $key . '":' . $id . '}%');
        });

        $activity = ActivityLog::leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->where('log_name', '!=', 'system_debug')
            // Nested so the log_name filter applies to every subject, not only the bill's own entries
            ->where(function($subjects) use ($billId, $bill, $charges, $hasId) {
                $subjects->where(fn($q) => $q->where('subject_type', 'App\Models\Bill')->where('subject_id', $billId))
                    ->orWhere(fn($q) => $q->where('subject_type', 'App\Models\Address')
                        ->whereIn('subject_id', array_filter([$bill->pickup_address_id, $bill->delivery_address_id])))
                    ->orWhere(fn($q) => $hasId($q->where('subject_type', 'App\Models\Charge'), 'bill_id', (int)$billId));

                foreach($charges as $charge)
                    $subjects->orWhere(fn($q) => $hasId($q->where('subject_type', 'App\Models\LineItem'), 'charge_id', (int)$charge->charge_id));
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

