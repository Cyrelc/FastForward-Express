<?php

namespace App\Http\Controllers;

use App\Http\Collectors;
use App\Http\Models;
use App\Http\Repos;
use App\Http\Repos\AccountRepo;
use App\Http\Repos\UserRepo;
use App\Http\Validation;
use App\Models\AccountUser;
use App\Models\Contact;
use App\Models\User;
// use App\Services\AccountUserService;
use App\Services\ContactService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use DB;

class AccountUserController extends Controller {
    public function checkIfAccountUserExists(Request $req) {
        $userRepo = new Repos\UserRepo();
        foreach($req->email_addresses as $email) {
            $emailAddress = trim($email['email']);
            $user = $userRepo->GetUserByPrimaryEmail($emailAddress);
            if($user && $user->accountUsers) {
                $accountRepo = new Repos\AccountRepo();
                $contact = $user->accountUsers[0]->contact;
                $accounts = array();
                foreach($user->accountUsers as $accountUser) {
                    $account = $accountRepo->GetById($accountUser->account_id);
                    array_push($accounts, [
                        'account_number' => $account->account_number,
                        'label' => $account->name,
                        'value' => $account->account_id,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'email_in_use' => true,
                    'contact_id' => $contact ? $contact->contact_id : null,
                    'name' => $contact ? $contact->displayName() : null,
                    'accounts' => $accounts
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'email_in_use' => false
        ]);
    }

    public function delete(Request $req, $contactId, $accountId) {
        $accountUser = AccountUser::where(['contact_id' => $contactId, 'account_id' => $accountId])->first();
        if($req->user()->cannot('delete', $accountUser))
            abort(403);

        if(AccountUser::where('account_id', $accountId)->count() == 1) {
            return response()->json([
                'message' => 'Minimum of one account user required',
                'errors' => ['min_count' => ['Account must have at least one user']]
            ], 403);
        } else if($accountUser->is_primary) {
            return response()->json([
                'message' => 'Cannot delete the primary account user. Please select another primary user and then try again',
                'errors' => ['is_primary' => ['Unable to delete primary account user']]
            ], 403);
        } else {
            DB::beginTransaction();

            $accountUser->delete();

            // if that was the only account the user was linked to then we can delete the remaining data
            if(AccountUser::where('contact_id', $contactId)->count() == 0) {
                $contactService = new ContactService();
                $contactService->delete($accountUser->contact_id);

                User::find($accountUser->user_id)->delete();
            }
            DB::commit();
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function getAccountUserModel(Request $req, $accountId, $contactId = null) {
        $userModelFactory = new Models\User\UserModelFactory;

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

        $userModelFactory = new Models\User\UserModelFactory();
        $users = $userModelFactory->GetAccountUsers($accountId);

        return json_encode($users);
    }

    public function linkAccountUser(Request $req, $contactId, $accountId) {
        $userRepo = new Repos\UserRepo();
        $contact = Contact::findOrFail($contactId);
        $targetUser = $userRepo->GetUserByPrimaryEmail($contact->primary_email->email);
        if($req->user()->cannot('linkUser', $targetUser))
            abort(403);
        else if($userRepo->GetAccountUser($contactId, $accountId) != null)
            return response()->json(['message' => 'User is already linked to this account', 'errors' => ['already_linked' => ['User is already linked to this account']]], 403);

        DB::beginTransaction();

        $userRepo = new Repos\UserRepo();
        $userRepo->LinkAccountUser($contactId, $accountId);

        DB::commit();

        return response()->json([
            'success' => true,
        ]);
    }

    public function setPrimary(Request $req, $accountId, $contactId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('updateAccountUsersBasic', $account))
            abort(403);

        $userRepo = new Repos\UserRepo();
        $userRepo->SetAccountUserAsPrimary($accountId, $contactId);

        return response()->json(['success' => true]);
    }

    public function storeAccountUser(Request $req) {
        $permissionModelFactory = new Models\Permission\PermissionModelFactory();

        $accountRepo = new Repos\AccountRepo();
        $userRepo = new Repos\UserRepo();

        $account = $accountRepo->GetById($req->account_id);
        $originalAccountUser = $userRepo->GetAccountUser($req->contact_id, $req->account_id);

        if($originalAccountUser ? $req->user()->cannot('updateBasic', $originalAccountUser) : $req->user()->cannot('createAccountUsers', $account))
            abort(403);

        $permissions = $permissionModelFactory->GetAccountUserPermissions($req->user(), $originalAccountUser, $account);

        $partialsValidation = new Validation\PartialsValidationRules();

        $userId = $originalAccountUser ? $originalAccountUser->user_id : null;

        $partialsValidation = $partialsValidation->GetContactValidationRules($req, $userId);
        // $partialsValidation['rules'] = array_merge($partialsValidation['rules'], [
        //     'email' => 'required|string|email|max:255|unique:users',
        // ]);
        $this->validate($req, $partialsValidation['rules'], $partialsValidation['messages']);

        DB::beginTransaction();

        $userCollector = new Collectors\UserCollector();

        //Begin Contact
        $contactId = $req->contact_id;
        $contactService = new ContactService();

        $userRepo = new Repos\UserRepo();

        if($contactId == null) {
            $isEdit = false;
            $contactId = $contactService->create($req)->contact_id;
        } else {
            $contactService->update($req);
            $isEdit = true;
        }

        //Begin User
        $primaryEmailAddress = Contact::find($contactId)->primary_email->email;

        $user = $userCollector->CollectAccountUser($req, $contactId, $primaryEmailAddress, $userId);

        if($isEdit) {
            if($userId == null) {
                $user = $userRepo->Insert($user);
                $userRepo->AddUserToAccountUser($contactId, $userId);
            } else {
                $user['id'] = $userId;
                $user = $userRepo->Update($user, $permissions['editPermissions']);
            }
        } else {
            $user = $userRepo->Insert($user);
            $userId = $user->id;
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
}
?>

