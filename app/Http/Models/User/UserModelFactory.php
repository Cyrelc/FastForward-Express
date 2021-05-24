<?php

namespace App\Http\Models\User;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\User;

class UserModelFactory {
    public function getAccountUser($req, $contactId, $accountId) {
        $model = new AccountUserFormModel();

        $contactModelFactory = new Models\Partials\ContactModelFactory();
        $permissionModelFactory = new Models\Permission\PermissionModelFactory();

        $accountRepo = new Repos\AccountRepo();
        $activityLogRepo = new Repos\ActivityLogRepo();
        $userRepo = new Repos\UserRepo();

        $model->account_user = $userRepo->GetAccountUser($contactId, $accountId);
        $model->permissions = $permissionModelFactory->GetAccountUserPermissions($req->user(), $model->account_user);
        $model->contact = $contactModelFactory->GetEditModel($contactId, false);

        if($model->permissions['viewPermissions'])
            $model->account_user_model_permissions = $permissionModelFactory->GetAccountUserModelPermissions($model->account_user);

        if($req->user()->can('viewActivityLog', $model->account_user)) {
            $model->activity_log = $activityLogRepo->GetAccountUserActivityLog($contactId);
            foreach($model->activity_log as $key => $log)
                $model->activity_log[$key]->properties = json_decode($log->properties);
        }

        $model->belongs_to = $accountRepo->GetMyAccountsStructured(\App\User::where('user_id', $model->account_user->user_id)->first());

        return $model;
    }

    public function getAccountUserCreateModel($req, $account) {
        $model = new AccountUserFormModel();

        $contactModelFactory = new Models\Partials\ContactModelFactory();
        $permissionModelFactory = new Models\Permission\PermissionModelFactory();

        $model->contact = $contactModelFactory->GetCreateModel();
        $model->permissions = $permissionModelFactory->GetAccountUserPermissions($req->user(), null, $account);
        if($req->user()->can('updateAccountUserPermissions', $account))
            $model->account_user_model_permissions = $permissionModelFactory->GetAccountUserModelPermissions(null);

        return $model;
    }
}

?>

