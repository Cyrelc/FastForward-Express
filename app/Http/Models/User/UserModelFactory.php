<?php

namespace App\Http\Models\User;

use App\Http\Repos;
use App\Http\Repos\UserRepo;
use App\Http\Models;
use App\Http\Models\User;

class UserModelFactory {
    public function getAccountUser($req, $contactId, $accountId) {
        $model = new AccountUserFormModel();

        $contactService = new \App\Services\ContactService();
        $permissionModelFactory = new Models\Permission\PermissionModelFactory();

        $accountRepo = new Repos\AccountRepo();
        $activityLogRepo = new Repos\ActivityLogRepo();

        $model->account_user = AccountUser::where(['contact_id' => $contactId, 'account_id' => $accountId])->firstOrFail();
        $model->permissions = $permissionModelFactory->GetAccountUserPermissions($req->user(), $model->account_user);
        $model->contact = $contactService->getFull($contactId, false);

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
        $userRepo = new UserRepo();

        $accountUsers = $userRepo->GetAccountUsers($accountId);
        foreach($accountUsers as $key => $accountUser) {
            $accountUsers[$key]['roles'] = $accountUser->contact->email_roles();
        }

        return $accountUsers;
    }

    public function getAccountUserCreateModel($req, $account) {
        $model = new AccountUserFormModel();

        $contactService = new \App\Services\ContactService();
        $permissionModelFactory = new Models\Permission\PermissionModelFactory();

        $model->contact = $contactService->getCreate();
        $model->permissions = $permissionModelFactory->GetAccountUserPermissions($req->user(), null, $account);
        if($req->user()->can('updateAccountUserPermissions', $account))
            $model->account_user_model_permissions = $permissionModelFactory->GetAccountUserModelPermissions(null);

        return $model;
    }

    public function getUserConfiguration($req) {
        $model = new UserConfigurationModel();
        $userRepo = new Repos\UserRepo();

        $permissionModelFactory = new Models\Permission\PermissionModelFactory;

        $model->frontEndPermissions = $permissionModelFactory->getFrontEndPermissionsForUser($req->user());
        $model->authenticatedEmployee = $req->user()->employee;
        $model->authenticatedAccountUsers = $req->user()->accountUsers;
        $model->authenticatedUserId = $req->user()->user_id;
        $model->is_impersonating = $req->session()->has('original_user_id');
        $model->user_settings = $userRepo->GetSettings($req->user()->user_id);
        if($model->authenticatedEmployee)
            $model->contact = $req->user()->employee->contact;
        else if (count($model->authenticatedAccountUsers) > 0)
            $model->contact = $model->authenticatedAccountUsers[0]->contact;
        else if ($req->user()->hasRole('superAdmin'))
            $model->contact = ['first_name' => $req->user()->email];

        return $model;
    }
}

?>

