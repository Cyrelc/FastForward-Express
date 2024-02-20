<?php
namespace App\Http\Repos;

use App\Models\AccountUser;
use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\Employee;
use App\Models\EmailAddress;
use App\Models\PhoneNumber;
use DB;

class ActivityLogRepo {
    public function GetAccountActivityLog($accountId) {
        $accountRepo = new AccountRepo();
        $userRepo = new UserRepo();
        $account = $accountRepo->GetById($accountId);
        $activity = ActivityLog::where([['subject_type', 'App\Account'], ['subject_id', $accountId]])
            ->orWhere(function($addresses) use ($account) {
                $addresses->where('subject_type', 'App\Models\Address');
                $addresses->whereIn('subject_id', [$account->billing_address_id, $account->shipping_address_id]);
            })
            ->leftJoin('users', 'users.user_id', '=', 'activity_log.causer_id')
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

        $activity = ActivityLog::where([['subject_type', 'App\Models\Contact'], ['subject_id', $contactId]])
            ->orWhere([['subject_type', 'App\Models\Contact'], ['subject_id', $contactId]])
            ->orWhere([['subject_type', 'App\User'], ['subject_id', $userId[0]]])
            ->orWhere(function($phones) use ($phoneNumberIds) {
                $phones->where('subject_type', 'App\Models\PhoneNumber');
                $phones->whereIn('subject_id', $phoneNumberIds->toArray());
            })
            ->orWhere(function($emails) use ($emailAddressIds) {
                $emails->where('subject_type', 'App\Models\EmailAddress');
                $emails->whereIn('subject_id', $emailAddressIds->toArray());
            })->leftJoin('users', 'users.user_id', '=', 'activity_log.causer_id')
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

        $activity = ActivityLog::leftJoin('users', 'users.user_id', '=', 'activity_log.causer_id')
            ->where('log_name', '!=', 'system_debug')
            ->where([['subject_type', 'App\Models\Bill'], ['subject_id', $billId]])
            ->orWhere([['subject_type', 'App\Models\Address'], ['subject_id', $bill->pickup_address_id]])
            ->orWhere([['subject_type', 'App\Models\Address'], ['subject_id', $bill->delivery_address_id]])
            ->orWhere([['subject_type', 'App\Models\Address'], ['subject_id', $bill->delivery_address_id]])
            ->orWhere([['subject_type', 'App\Models\Address'], ['subject_id', $bill->pickup_address_id]])
            ->orWhere([['subject_type', '=', 'App\Models\Charge'], ['properties', 'like', '%"bill_id":' . $bill->bill_id . '%']]);

        foreach($charges as $charge)
            $activity->orWhere([['subject_type', 'App\Models\LineItem'], ['properties', 'like', '%"charge_id":' . $charge->charge_id . '%']]);

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

        $contactIds = \App\EmployeeEmergencyContact::where('employee_id', $employeeId)->pluck('contact_id')->toArray();
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
            ->leftJoin('users', 'users.user_id', '=', 'activity_log.causer_id')
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

