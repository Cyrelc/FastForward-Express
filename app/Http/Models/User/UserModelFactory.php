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

    public function getAccountUsers($accountId) {
        $userRepo = new Repos\UserRepo();
        $contactModelFactory = new Models\Partials\ContactModelFactory();

        $accountUsers = $userRepo->GetAccountUsers($accountId);
        foreach($accountUsers as $key => $accountUser) {
            $accountUsers[$key]['roles'] = $contactModelFactory->GetRolesFromEmails($accountUser->contact_id);
        }

        return $accountUsers;
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

    public function getUserConfiguration($req) {
        $contactRepo = new Repos\ContactRepo();
        $userRepo = new Repos\UserRepo();

        $model = new UserConfigurationModel();

        $permissionModelFactory = new Models\Permission\PermissionModelFactory;

        $model->frontEndPermissions = $permissionModelFactory->getFrontEndPermissionsForUser($req->user());
        $model->authenticatedEmployee = $req->user()->employee;
        $model->authenticatedAccountUsers = $req->user()->accountUsers;
        $model->authenticatedUserId = $req->user()->user_id;
        $model->is_impersonating = $req->session()->has('original_user_id');
        $model->user_settings = $userRepo->GetSettings($req->user()->user_id);
        if($model->authenticatedEmployee)
            $model->contact = $contactRepo->GetById($req->user()->employee->contact->contact_id);
        else if (count($model->authenticatedAccountUsers) > 0)
            $model->contact = $contactRepo->GetById($model->authenticatedAccountUsers[0]->contact_id);
        else if ($req->user()->hasRole('superAdmin'))
            $model->contact = ['first_name' => $req->user()->email];

        return $model;
    }
}

?>

