<?php
namespace App\Http\Repos;

use App\AccountUser;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserRepo
{
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

    public function DeleteAccountUserByContactId($contactId) {
        $accountUser = AccountUser::where('contact_id', $contactId)->first();
        $user = User::where('user_id', $accountUser->user_id)->first();

        $user->delete();
        $accountUser->delete();

        $contactRepo = new ContactRepo();
        $contactRepo->Delete($contactId);
    }

    public function GetAccountUserByContactId($contact_id) {
        $accountUser = \App\AccountUser::where('contact_id', $contact_id);

        return $accountUser->first();
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

    public function GetAccountUsers($account_id) {
        $accountUsers = AccountUser::where('account_id', '=', $account_id)
            ->leftJoin('contacts', 'account_users.contact_id', '=', 'contacts.contact_id')
            ->leftJoin('users', 'account_users.user_id', '=', 'users.user_id')
            ->leftJoin('email_addresses', 'account_users.contact_id', '=', 'email_addresses.contact_id')
            ->leftJoin('phone_numbers', 'account_users.contact_id', '=', 'phone_numbers.contact_id')
            ->where('email_addresses.is_primary', true)
            ->where('phone_numbers.is_primary', true)
            ->select(
                'account_users.contact_id',
                'users.user_id',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as name'),
                'email_addresses.email as primary_email',
                'phone_numbers.phone_number as primary_phone',
                'contacts.position as position',
                'account_users.is_primary as is_primary'
            );

        return $accountUsers->get();
    }

    public function GetById($id) {
        $user = User::where('user_id', '=', $id)->first();

        return $user;
    }

    public function GetUserByEmployeeId($employeeId) {
        $employee = \App\Employee::where('employee_id', $employeeId)->first();
        $user = \App\User::where('user_id', $employee->user_id)->first();

        return $user;
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
        if(isset($user->is_locked))
            $old->is_locked = $user['is_locked'];
        $old->email = $user['email'];

        $old->save();

        return $old;
    }
}
