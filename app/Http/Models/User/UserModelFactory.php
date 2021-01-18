<?php

	namespace App\Http\Models\User;

	use App\Http\Repos;
	use App\Http\Models;
	use App\Http\Models\User;

	class UserModelFactory {
        public function getAccountUserByContactId($contactId) {
            $model = new AccountUserFormModel();

            $accountRepo = new Repos\AccountRepo();
            $activityLogRepo = new Repos\ActivityLogRepo();
            $userRepo = new Repos\UserRepo();
            $contactModelFactory = new Models\Partials\ContactModelFactory();

            $accountUser = $userRepo->GetAccountUserByContactId($contactId);

            $model->account_id = $accountUser->account_id;
            $model->contact = $contactModelFactory->GetEditModel($contactId, false);
            $model->belongs_to = $userRepo->GetAccountsUserBelongsTo($accountUser->user_id);
            $model->activity_log = $activityLogRepo->GetAccountUserActivityLog($contactId);
            foreach($model->activity_log as $key => $log)
                $model->activity_log[$key]->properties = json_decode($log->properties);

            return $model;
        }

        public function getCreateModel() {
            $model = new AccountUserFormModel();

            $contactModelFactory = new Models\Partials\ContactModelFactory();

            $model->contact = $contactModelFactory->GetCreateModel();

            return $model;
        }
    }
?>

