<?php
namespace App\Http\Repos;

use App\User;
use Illuminate\Support\Facades\Hash;

class UserRepo
{
    public function GetById($id) {
        $user = User::where('user_id', '=', $id)->first();

        return $user;
    }

    public function AddUserToAccountUser($contact_id, $user_id) {
        $accountUser = \App\AccountUser::where('contact_id', $contact_id)->first();

        $accountUser->user_id = $user_id;

        $accountUser->save();
    }

    public function ChangePassword($userId, $password) {
        $old = $this->GetById($userId);

        $old->password = $password;
        $old->save();
    }

    public function GetAccountUserByContactId($contact_id) {
        $accountUser = \App\AccountUser::where('contact_id', $contact_id);

        return $accountUser->first();
    }

    public function GetUserByEmployeeId($employeeId) {
        $employee = \App\Employee::where('employee_id', $employeeId)->first();
        $user = \App\User::where('user_id', $employee->user_id)->first();

        return $user;
    }

    public function Insert($user) {
        $new = new User;

        $user = array_merge($user, array(
            'password' => Hash::make(str_random(15)),
            'is_locked' => false,
            'login_attempts' => 0,
            'remember_token' => null
        ));

        $new = $new->create($user);

        return $new;
    }

    public function InsertAccountUser($account_user) {
        $new = new \App\AccountUser;
        
        $new = $new->create($account_user);

        return $new;
    }

    public function Update($user) {
        $old = $this->GetById($user['user_id']);

        // no support for usernames atm
        // $old->username = $user['username'];
        $old->is_locked = $user['is_locked'];
        $old->email = $user['email'];

        $old->save();

        return $old;
    }
}
