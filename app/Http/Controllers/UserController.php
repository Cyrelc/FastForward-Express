<?php

namespace App\Http\Controllers;

use App\Http\Repos;
use App\Http\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use DB;

class UserController extends Controller {
    public function changePassword(Request $req, $userId) {
        try{
            $userValidation = new \App\Http\Validation\UserValidationRules();
            $temp = $userValidation->GetValidationRules($req);

            $this->validate($req, $temp['rules'], $temp['messages']);

            $password = Hash::make(preg_replace('/\s+/', '', $req->password));

            $userRepo = new Repos\UserRepo();
            $userRepo->ChangePassword($userId, $password);

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function createAccountUser(Request $req, $id) {
        $userModelFactory = new User\UserModelFactory();
        $model = $userModelFactory->GetCreateModel();
        $model->account_id = $id;
        return view('accounts.editUser', compact('model'));
    }

    public function getAccountUsers(Request $req, $id) {
        $userModelFactory = new User\UserModelFactory();
        $model = $userModelFactory->getAccountUsers($id);
        return json_encode($model);
    }

    public function editAccountUser(Request $req, $contact_id) {
        $userModelFactory = new User\UserModelFactory();
        $selectionsRepo = new Repos\SelectionsRepo();

        $model = $userModelFactory->getAccountUserByContactId($contact_id);
        return view('accounts.editUser', compact('model'));
    }

    public function storeAccountUser(Request $req) {
        DB::beginTransaction();
        try {
            $partialsValidation = new \App\Http\Validation\PartialsValidationRules();

            $temp = $partialsValidation->GetContactValidationRules($req, true, true);

            $this->validate($req, $temp['rules'], $temp['messages']);

            $contactCollector = new \App\Http\Collectors\ContactCollector();
            $userCollector = new \App\Http\Collectors\UserCollector();

            //Begin Contact
            $contactId = $req->contact_id;
            $contactRepo = new Repos\ContactRepo();
            $userRepo = new Repos\UserRepo();
            $contact = $contactCollector->GetContact($req);
            if($contactId == '') {
                $isEdit = false;
                $contactId = $contactRepo->Insert($contact)->contact_id;
            }
            else {
                $contactRepo->Update($contact);
                $isEdit = true;
            }
            //End Contact
            $contactCollector->ProcessPhonesForContact($req, $contactId);
            $contactCollector->ProcessEmailsForContact($req, $contactId);
            //Begin User
            $emailRepo = new Repos\EmailAddressRepo();

            $user = $userCollector->Collect($req);

            if($isEdit) {
                $userId = $userRepo->GetAccountUserByContactId($req->contact_id)->user_id;
                if($userId == null) {
                    $user = $userRepo->Insert($user);
                    $userRepo->AddUserToAccountUser($req->contact_id, $user->user_id);
                } else {
                    $user['user_id'] = $userId;
                    $userRepo->Update($user);
                }
            } else {
                $userId = $userRepo->Insert($user)->user_id;
                $accountUser = $userCollector->CollectAccountUser($req->account_id, $contactId, false, $userId);
                $accountUserId = $userRepo->InsertAccountUser($accountUser)->account_user_id;
            }
            //End User
            DB::commit();

            return ['success' => true];
        } catch (exception $e) {
            DB::rollBack();

            return response()->json([
                'success'=> false,
                'error'=>$e->getMessage()
            ]);
        }
    }
}
?>

