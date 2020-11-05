<?php

namespace App\Http\Controllers;

use App\Http\Repos;
use App\Http\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use DB;

class UserController extends Controller {
    public function changePassword(Request $req, $userId) {
        DB::beginTransaction();
        $userValidation = new \App\Http\Validation\UserValidationRules();
        $temp = $userValidation->GetValidationRules($req);

        $this->validate($req, $temp['rules'], $temp['messages']);

        $userRepo = new Repos\UserRepo();
        $userRepo->ChangePassword($userId, $password);

        DB::commit();
        return response()->json([
            'success' => true
        ]);
    }

    public function deleteAccountUser($contactId) {
        //Do I have permission?
        DB::beginTransaction();

        $userRepo = new Repos\UserRepo();
        $accountId = $userRepo->GetAccountUserByContactId($contactId)->account_id;
        if($userRepo->CountAccountUsers($accountId) > 1)
            $userRepo->DeleteAccountUserByContactId($contactId);
        else
            return response()->json(['message' => 'Minimum of one account user required', 'errors' => ['min_count' => ['Account must have at least one user']]], 403);

        DB::commit();
    }

    public function generatePassword() {
        $filePath = '../resources/assets/passwordSeed.txt';
        $passwordSeedFile = fopen($filePath, 'r');
        $passwordSeedString = fread($passwordSeedFile,filesize($filePath));
        $wordDictionary = explode("\r\n", $passwordSeedString);
        $suggestedPassword = "";
        $randomNumber = rand(1, 12);
        for($i = 0; $i < 4; $i++) {
            if($i > 0)
                $suggestedPassword .= " ";
            if($randomNumber%($i + 1) === 0)
                $suggestedPassword .= ucfirst($wordDictionary[rand(0, count($wordDictionary))]);
            else
                $suggestedPassword .= $wordDictionary[rand(0, count($wordDictionary))];
        }
        return json_encode($suggestedPassword);
    }

    public function getAccountUserModel($contactId) {
        $userModelFactory = new \App\Http\Models\User\UserModelFactory;
        $accountUser = $userModelFactory->getAccountUserByContactId($contactId);

        return json_encode($accountUser);
    }

    public function getAccountUsers($accountId) {
        $userRepo = new Repos\UserRepo();
        $users = $userRepo->GetAccountUsers($accountId);
        return json_encode($users);
    }

    public function setPrimaryAccountUser($contact_id) {
        $userRepo = new Repos\UserRepo();
        $userRepo->setPrimaryAccountUser($contact_id);
        return;
    }

    public function storeAccountUser(Request $req) {
        DB::beginTransaction();

        $partialsValidation = new \App\Http\Validation\PartialsValidationRules();
        $userRepo = new Repos\UserRepo();

        $oldUser = $userRepo->GetAccountUserByContactId($req->contact_id);
        $userId = $oldUser ? $oldUser->user_id : null;

        $temp = $partialsValidation->GetContactValidationRulesV2($req, $userId, $req->contact_id);

        $this->validate($req, $temp['rules'], $temp['messages']);

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
        $user = $userCollector->CollectAccountUser($req->account_id, $contactId, $primaryEmailAddress, $userId);

        if($isEdit) {
            if($userId == null) {
                $user = $userRepo->Insert($user);
                $userRepo->AddUserToAccountUser($contactId, $userId);
            } else {
                $user['user_id'] = $userId;
                $userRepo->Update($user);
            }
        } else {
            $userId = $userRepo->Insert($user)->user_id;
            $accountUser = $userCollector->CollectAccountUser($req->account_id, $contactId, $primaryEmailAddress, $userId);
            $accountUserId = $userRepo->InsertAccountUser($accountUser)->account_user_id;
        }
        //End User
        DB::commit();

        return ['success' => true];
    }
}
?>

