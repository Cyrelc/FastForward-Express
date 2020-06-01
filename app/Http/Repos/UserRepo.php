<?php
namespace App\Http\Repos;

use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    public function CountAccountUsers($account_id) {
        $count = \App\AccountUser::where('account_id', $account_id)->count();

        return $count;
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

    public function GetAccountUserIds ($account_id) {
        $query = \App\AccountUser::where('account_id', $account_id)
            ->leftJoin('email_addresses', 'email_addresses.contact_id', '=', 'account_users.contact_id')
            ->leftJoin('phone_numbers', 'phone_numbers.contact_id', '=', 'account_users.contact_id');
        $accountUsers['contact_ids'] = $query->pluck('account_users.contact_id');
        $accountUsers['user_ids'] = $query->pluck('user_id');
        $accountUsers['email_ids'] = $query->pluck('email_address_id');
        $accountUsers['phone_ids'] = $query->pluck('phone_number_id');

        return $accountUsers;
    }

    public function Insert($user) {
        $new = new User;

        $user = array_merge($user, array(
            'password' => Hash::make(Str::random(15)),
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

    public function SetPrimaryAccountUser($contact_id) {
        $newPrimary = \App\AccountUser::where($contact_id, 'contact_id')->first();
        $oldPrimary = \App\AccountUser::where('account_id', $newPrimary->account_id)
            ->where('is_primary', true)
            ->first();
        if(isset($oldPrimary->contact_id)) {
            $oldPrimary->is_primary = false;
            $oldPrimary->save();
        }
        $newPrimary->is_primary = true;
        $newPrimary->save();

        return $newPrimary;
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
