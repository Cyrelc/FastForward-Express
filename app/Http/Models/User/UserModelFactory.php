<?php

	namespace App\Http\Models\User;

	use App\Http\Repos;
	use App\Http\Models;
	use App\Http\Models\User;

	class UserModelFactory {
        public function getAccountUsers($account_id) {
            try {
                $contactRepo = new Repos\ContactRepo();
                $accountUsers = $contactRepo->getAccountUsers($account_id);
            }
            catch (exception $e) {

            }

            return $accountUsers;
        }

        public function getAccountUserByContactId($contact_id) {
            $model = new AccountUserFormModel();

            $userRepo = new Repos\UserRepo();
            $contactModelFactory = new Models\Partials\ContactModelFactory();

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

