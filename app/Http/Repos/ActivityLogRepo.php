<?php
namespace App\Http\Repos;

use App\ActivityLog;
use DB;

class ActivityLogRepo {
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

