<?php

namespace App\Http\Controllers;

use App\Http\Repos;
use App\Http\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use DB;

class UserController extends Controller {
    public function changePassword(Request $req, $userId) {
        $userRepo = new Repos\UserRepo();
        $originalUser = $userRepo->GetById($userId);
        if($originalUser == null)
            abort(404, 'Requested user not found');
        if($req->user()->cannot('updatePassword', $originalUser))
            abort(403);

        DB::beginTransaction();
        $userValidation = new \App\Http\Validation\UserValidationRules();
        $temp = $userValidation->GetPasswordValidationRules();

        $this->validate($req, $temp['rules'], $temp['messages']);

        $userRepo = new Repos\UserRepo();
        $userRepo->ChangePassword($userId, $req->password);

        DB::commit();
        return response()->json([
            'success' => true
        ]);
    }

    public function checkIfAccountUserExists(Request $req) {
        $userRepo = new Repos\UserRepo();
        foreach($req->emails as $email) {
            $user = $userRepo->GetUserByPrimaryEmail($email['email']);
            if($user && $user->accountUsers) {
                $accountRepo = new Repos\AccountRepo();
                $contactRepo = new Repos\ContactRepo();
                $contact = $contactRepo->GetById($user->accountUsers[0]->contact_id);
                $accounts = array();
                foreach($user->accountUsers as $accountUser) {
                    $account = $accountUser->account;
                    array_push($accounts, [
                        'account_number' => $account->account_number,
                        'label' => $account->name,
                        'value' => $account->account_id,
                        ]
                    );
                }
                return response()->json([
                    'success' => true,
                    'email_in_use' => true,
                    'contact_id' => $contact ? $contact->contact_id : null,
                    'name' => $contact ? $contact->first_name . ' ' . $contact->last_name : null,
                    'accounts' => $accounts
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'email_in_use' => false
        ]);
    }

    public function deleteAccountUser(Request $req, $contactId, $accountId) {
        if($req->user()->cannot('delete', AccountUser::class))
            abort(403);

        DB::beginTransaction();

        $userRepo = new Repos\UserRepo();

        if($userRepo->CountAccountUsers($accountId) > 1)
            $userRepo->DeleteAccountUser($contactId, $accountId);
        else
            return response()->json(['message' => 'Minimum of one account user required', 'errors' => ['min_count' => ['Account must have at least one user']]], 403);

        DB::commit();
    }

    public function getAccountUserModel(Request $req, $accountId, $contactId = null) {
        $userModelFactory = new User\UserModelFactory;

        if($contactId) {
            $model = $userModelFactory->getAccountUser($req, $contactId, $accountId);

            if($contactId && $req->user()->cannot('view', $model->account_user))
                abort(403);
        } else {
            if($req->user()->cannot('create', AccountUser::class))
                abort(403);

            $accountRepo = new Repos\AccountRepo();
            $model = $userModelFactory->getAccountUserCreateModel($req, $accountRepo->GetById($accountId));
        }

        return json_encode($model);
    }

    public function getAccountUsers(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('view', $account))
            abort(403);

        $userModelFactory = new User\UserModelFactory();
        $users = $userModelFactory->GetAccountUsers($accountId);

        return json_encode($users);
    }

    public function getAuthenticatedUser(Request $req) {
        $contactRepo = new Repos\ContactRepo();
        if($req->user()->employee)
            return $contactRepo->GetById($req->user()->employee->contact_id);
        elseif($req->user()->accountUser)
            return $contactRepo->GetById($req->user()->accountUser->contact_id);
    }

    public function impersonate(Request $req) {
        $userRepo = new Repos\UserRepo();
        $impersonateUser = null;

        if($req->employee_id) {
            if($req->user()->cannot('employees.impersonate.*.*'))
                abort(403);

            $impersonateUser = $userRepo->GetUserByEmployeeId($req->employee_id);
        } else {
            if($req->user()->cannot('accountUsers.impersonate.*'))
                abort(403);

            $impersonateUser = $userRepo->GetAccountUser($req->contact_id, $req->account_id);
        }

        if($req->session()->missing('original_user_id'))
            $req->session()->put('original_user_id', Auth::user()->user_id);

        Auth::loginUsingId($impersonateUser->user_id);
    }

    public function LinkAccountUser(Request $req, $contactId, $accountId) {
        $contactRepo = new Repos\ContactRepo();
        $userRepo = new Repos\UserRepo();
        $targetUser = $userRepo->GetUserByPrimaryEmail($contactRepo->GetById($contactId)->primary_email->email);
        if($req->user()->cannot('linkUser', $targetUser))
            abort(403);
        else if($userRepo->GetAccountUser($contactId, $accountId) != null)
            return response()->json(['message' => 'User is already linked to this account', 'errors' => ['already_linked' => ['User is already linked to this account']]], 403);

        DB::beginTransaction();

        $userRepo = new Repos\UserRepo();
        $userRepo->LinkAccountUser($contactId, $accountId);

        DB::commit();
    }
    // public function setPrimaryAccountUser($contact_id) {
    //     $userRepo = new Repos\UserRepo();
    //     $userRepo->setPrimaryAccountUser($contact_id);
    //     return;
    // }

    public function sendPasswordResetEmail(Request $req, $userId) {
        $userRepo = new Repos\UserRepo();
        $targetUser = $userRepo->GetById($userId);

        if(!$targetUser)
            abort(404);

        if($req->user()->cannot('updatePassword', $targetUser))
            abort(403);

        $token = Password::getRepository()->create($targetUser);
        $targetUser->sendPasswordResetNotification($token);

        return response()->json([
            'success' => true,
            'email' => $targetUser->email
        ]);
    }

    public function storeAccountUser(Request $req) {
        $permissionModelFactory = new \App\Http\Models\Permission\PermissionModelFactory();

        $accountRepo = new Repos\AccountRepo();
        $userRepo = new Repos\UserRepo();

        $account = $accountRepo->GetById($req->account_id);
        $originalAccountUser = $userRepo->GetAccountUser($req->contact_id, $req->account_id);

        if($originalAccountUser ? $req->user()->cannot('updateBasic', $originalAccountUser) : $req->user()->cannot('createAccountUsers', $account))
            abort(403);

        $permissions = $permissionModelFactory->GetAccountUserPermissions($req->user(), $originalAccountUser, $account);

        $partialsValidation = new \App\Http\Validation\PartialsValidationRules();

        $userId = $originalAccountUser ? $originalAccountUser->user_id : null;

        $temp = $partialsValidation->GetContactValidationRules($req, $userId, $req->contact_id);
        $this->validate($req, $temp['rules'], $temp['messages']);

        DB::beginTransaction();

        $contactCollector = new \App\Http\Collectors\ContactCollector();
        $userCollector = new \App\Http\Collectors\UserCollector();

        //Begin Contact
        $contactId = $req->contact_id;
        $contactRepo = new Repos\ContactRepo();
        $userRepo = new Repos\UserRepo();
        $contact = $contactCollector->GetContact($req, $contactId);
        if($contactId == null) {
            $isEdit = false;
            $contactId = $contactRepo->Insert($contact)->contact_id;
        }
        else {
            $contactRepo->Update($contact);
            $isEdit = true;
        }
        //End Contact
        $contactCollector->ProcessPhoneNumbersForContact($req, $contactId);
        $contactCollector->ProcessEmailAddressesForContact($req, $contactId);
        //Begin User
        $emailRepo = new Repos\EmailAddressRepo();

        $primaryEmailAddress = $emailRepo->GetPrimaryByContactId($contactId)->email;
        $user = $userCollector->CollectAccountUser($req, $contactId, $primaryEmailAddress, $userId);

        if($isEdit) {
            if($userId == null) {
                $user = $userRepo->Insert($user);
                $userRepo->AddUserToAccountUser($contactId, $userId);
            } else {
                $user['user_id'] = $userId;
                $user = $userRepo->Update($user, $permissions['editPermissions']);
            }
        } else {
            $user = $userRepo->Insert($user);
            $userId = $user->user_id;
            $accountUser = $userCollector->CollectAccountUser($req, $contactId, $primaryEmailAddress, $userId);
            $accountUserId = $userRepo->InsertAccountUser($accountUser)->account_user_id;
        }
        //End User
        //Begin User Permissions
        if($req->user()->can('updatePermissions', $userRepo->GetAccountUser($req->contact_id, $req->account_id))) {
            $permissions = $userCollector->CollectAccountUserPermissions($req);
            $permissionRepo = new Repos\PermissionRepo();
            $permissionRepo->assignUserPermissions($user, $permissions);
        }
        // End User Permissions

        DB::commit();

        return ['success' => true];
    }

    public function unimpersonate(Request $req) {
        $originalUserId = $req->session()->pull('original_user_id');
        if(!$originalUserId)
            abort(403);

        Auth::loginUsingId($originalUserId);
    }
}
?>

