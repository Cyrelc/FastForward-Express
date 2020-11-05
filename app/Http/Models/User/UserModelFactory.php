<?php

	namespace App\Http\Models\User;

	use App\Http\Repos;
	use App\Http\Models;
	use App\Http\Models\User;

	class UserModelFactory {
        public function getAccountUserByContactId($contact_id) {
            $model = new AccountUserFormModel();

            $userRepo = new Repos\UserRepo();
            $contactModelFactory = new Models\Partials\ContactModelFactory();

            $model->account_id = $userRepo->GetAccountUserByContactId($contact_id)->account_id;
            $model->contact = $contactModelFactory->GetEditModel($contact_id, false);
            
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

