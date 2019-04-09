<?php
namespace App\Http\Repos;

use App\ActivityLog;
use DB;

class ActivityLogRepo {
    public function GetAccountActivityLog($account_id) {
        $accountRepo = new AccountRepo();
        $userRepo = new UserRepo();
        $account = $accountRepo->GetById($account_id);
        $accountUserIds = $userRepo->GetAccountUserIds($account_id);
        $activity = ActivityLog::where([['subject_type', 'App\Account'], ['subject_id', $account_id]])
            ->orWhere(function($addresses) use ($account){
                $addresses->where('subject_type', 'App\Address');
                $addresses->whereIn('subject_id', [$account->billing_address_id, $account->shipping_address_id]);
            })
            ->orWhere(function($contacts) use ($accountUserIds) {
                $contacts->where('subject_type', 'App\Contact');
                $contacts->whereIn('subject_id', $accountUserIds['contact_ids']->toArray());
            })
            ->orWhere(function($users) use ($accountUserIds) {
                $users->where('subject_type', 'App\User');
                $users->whereIn('subject_id', $accountUserIds['user_ids']->toArray());
            })
            ->orWhere(function($phones) use ($accountUserIds) {
                $phones->where('subject_type', 'App\PhoneNumber');
                $phones->whereIn('subject_id', $accountUserIds['phone_ids']->toArray());
            })
            ->orWhere(function($emails) use ($accountUserIds) {
                $emails->where('subject_type', 'App\EmailAddress');
                $emails->whereIn('subject_id', $accountUserIds['email_ids']->toArray());
            })
            ->leftJoin('users', 'users.user_id', '=', 'activity_log.causer_id')
            ->select('activity_log.updated_at',
                'subject_type',
                'subject_id',
                'users.email as user_name',
                'description',
                'properties'
            );

            return $activity->get();
    }

    public function GetBillActivityLog($bill_id) {
        $billRepo = new BillRepo();
        $bill = $billRepo->GetById($bill_id);
        $activity = ActivityLog::where([['subject_type', 'App\Bill'], ['subject_id', $bill_id]])
            ->orWhere([['subject_type', 'App\Address'], ['subject_id', $bill->pickup_address_id]])
            ->orWhere([['subject_type', 'App\Address'], ['subject_id', $bill->delivery_address_id]])
            ->leftJoin('users', 'users.user_id', '=', 'activity_log.causer_id')
            ->select('activity_log.updated_at',
                'subject_type',
                'subject_id',
                'users.email as user_name',
                'description',
                'properties'
            );
        return $activity->get();
    }
}

?>

