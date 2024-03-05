<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Http\Collectors;
use App\Http\Repos;
use App\Http\Validation;
use App\Http\Models\User;
use App\Http\Resources\AuthenticatedUserResource;
use App\Services\ContactService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use DB;

class UserController extends Controller {
    public function changePassword(Request $req, $userId) {
        $originalUser = User::find($userId);
        if($originalUser == null)
            abort(404, 'Requested user not found');
        if($req->user()->cannot('updatePassword', $originalUser))
            abort(403);

        DB::beginTransaction();
        $userValidation = new \App\Http\Validation\UserValidationRules();
        $temp = $userValidation->GetPasswordValidationRules();

        $this->validate($req, $temp['rules'], $temp['messages']);

        $success = $originalUser->update(['password', \Hash::make($req->password)]);

        DB::commit();
        return response()->json([
            'success' => $success
        ]);
    }

    public function getAuthenticatedUser(Request $req) {
        if($req->user()->employee)
            return $req->user()->employee->contact;
        elseif($req->user()->accountUser)
            return $req->user()->accountUser->contact;
    }

    public function getUserConfiguration(Request $req) {
        return new AuthenticatedUserResource(Auth::user());
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
            $req->session()->put('original_user_id', Auth::user()->id);

        Auth::loginUsingId($impersonateUser->id);
    }

    public function sendPasswordResetEmail(Request $req, $userId) {
        $targetUser = User::findOrFail($userId);

        if($req->user()->cannot('updatePassword', $targetUser))
            abort(403);

        $token = Password::getRepository()->create($targetUser);
        $targetUser->sendPasswordResetNotification($token);

        return response()->json([
            'success' => true,
            'email' => $targetUser->email
        ]);
    }

    public function storeSettings(Request $req) {
        $userCollector = new Collectors\UserCollector();
        $userRepo = new Repos\UserRepo();

        $settings = $userCollector->collectSettings($req);

        $userRepo->storeSettings($req->user()->id, $settings);
    }

    public function unimpersonate(Request $req) {
        $originalUserId = $req->session()->pull('original_user_id');
        if(!$originalUserId)
            abort(403);

        Auth::loginUsingId($originalUserId);
    }
}
?>

