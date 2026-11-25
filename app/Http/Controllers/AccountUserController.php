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
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($req->account_id);
        if(!$account)
            abort(404);
        // Only someone who could add a user to this account has any reason to ask
        if($req->user()->cannot('createAccountUsers', $account))
            abort(403);

        $userRepo = new Repos\UserRepo();
        foreach($req->email_addresses ?? [] as $email) {
            $emailAddress = trim($email['email'] ?? '');
            $user = $userRepo->GetUserByPrimaryEmail($emailAddress);
            if(!$user)
                continue;

            // The address is taken, but only reveal who holds it (name, contact, accounts) to someone
            // allowed to link that user. Otherwise this would map any email to a person and their company.
            if($user->accountUsers->isEmpty() || $req->user()->cannot('linkUser', $user))
                return response()->json([
                    'success' => true,
                    'email_in_use' => true,
                    'contact_id' => null,
                    'name' => null,
                    'accounts' => []
                ]);

            // Staff see every account the user belongs to; a customer admin sees only the ones within
            // their own scope, not other companies the same person also works with
            $visibleAccountIds = $req->user()->can('accountUsers.create.*.*')
                ? null
                : $accountRepo->GetMyAccountIds($req->user(), $req->user()->can('accountUsers.create.children'));

            $contact = $user->accountUsers[0]->contact;
            $accounts = array();
            foreach($user->accountUsers as $accountUser) {
                if($visibleAccountIds !== null && !in_array($accountUser->account_id, $visibleAccountIds))
                    continue;
                $userAccount = $accountRepo->GetById($accountUser->account_id);
                array_push($accounts, [
                    'account_number' => $userAccount->account_number,
                    'label' => $userAccount->name,
                    'value' => $userAccount->account_id,
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
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);
        if(!$account)
            abort(404);
        // linkUser only checks that the user being linked is one of mine; the account they are being
        // linked INTO must be one I can add users to as well, or anyone could join any account
        if($req->user()->cannot('createAccountUsers', $account))
            abort(403);

        $userRepo = new Repos\UserRepo();
        $contact = Contact::findOrFail($contactId);
        $targetUser = $contact->primary_email ? $userRepo->GetUserByPrimaryEmail($contact->primary_email->email) : null;
        if(!$targetUser)
            abort(404);
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
        if(!$account)
            abort(404);
        $originalAccountUser = $userRepo->GetAccountUser($req->contact_id, $req->account_id);

        // A contact_id is only valid when editing a user of this account. Without this, any contact
        // in the system (staff included) could be passed in and overwritten, authorized only by the
        // caller's rights over their own account.
        if($req->contact_id && !$originalAccountUser)
            abort(403);

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

