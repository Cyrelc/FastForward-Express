<?php

namespace App\Http\Controllers;

use App\Http\Repos;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
}

?>
